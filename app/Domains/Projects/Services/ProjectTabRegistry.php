<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTab;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Projects\Support\OverviewProjectTab;
use App\Domains\Projects\Support\ProjectTabViewItem;
use App\Domains\Projects\Support\ResolvedProjectTab;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProjectTabRegistry
{
    /** @var array<string, ResolvedProjectTab>|null */
    private ?array $resolvedTabs = null;

    /**
     * @var array<string, Collection<string, ProjectTabUserPreference>>
     */
    private array $preferencesCache = [];

    /** @var array<string, array<int, ProjectTabViewItem>> */
    private array $tabItemsCache = [];

    private ?bool $hasProjectTabDefinitionsTable = null;

    public function __construct(
        private readonly ProjectTabCatalog $catalog,
        private readonly ProjectTabPreferenceStore $preferenceStore,
    ) {}

    /**
     * @return array<string, ResolvedProjectTab>
     */
    public function tabs(): array
    {
        if (is_array($this->resolvedTabs)) {
            return $this->resolvedTabs;
        }

        $tabs = $this->catalog->registeredTabs();
        if ($tabs === []) {
            $overview = ResolvedProjectTab::from(new OverviewProjectTab);
            $tabs = [$overview->key() => $overview];
        }

        $overrides = $this->projectTabDefinitionsTableExists()
            ? ProjectTabDefinition::query()
                ->whereIn('key', array_keys($tabs))
                ->get()
                ->keyBy('key')
            : collect();

        $resolvedTabs = [];
        foreach ($tabs as $tabKey => $tab) {
            $override = $overrides->get($tabKey);

            if ($override instanceof ProjectTabDefinition) {
                $tab = $tab->withOverrides(
                    label: $override->label,
                    sort: (int) $override->sort_order,
                    modeQueryParam: $override->mode_query_param,
                    isActive: (bool) $override->is_active,
                );
            }

            if (! $tab->isActive()) {
                continue;
            }

            $resolvedTabs[$tabKey] = $tab;
        }

        uasort($resolvedTabs, static function (ResolvedProjectTab $left, ResolvedProjectTab $right): int {
            if ($left->sort() === $right->sort()) {
                return strcmp($left->label(), $right->label());
            }

            return $left->sort() <=> $right->sort();
        });

        if (! array_key_exists('overview', $resolvedTabs)) {
            $overview = ResolvedProjectTab::from(new OverviewProjectTab);
            $resolvedTabs = ['overview' => $overview] + $resolvedTabs;
        }

        $this->resolvedTabs = $resolvedTabs;

        return $this->resolvedTabs;
    }

    /**
     * Register tab definitions by class name.
     *
     * @param  array<int, class-string<ProjectTab>>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        $this->catalog->registerDefinitions($definitions);

        $this->resolvedTabs = null;
        $this->flushRuntimeCaches();
    }

    /**
     * @param  array<string, array{modeParam:string,mode:string,detailParam:?string,detailId:string,isCreateMode:bool}>  $tabContext
     * @param  array<string, mixed>  $viewState
     * @return array<int, array{tab:string,component:string,props:array<string, mixed>,key:string}>
     */
    public function tabPanels(Project $project, ?User $user, array $tabContext = [], array $viewState = []): array
    {
        return collect($this->visibleTabItems($project, $user))
            ->map(function (ProjectTabViewItem $item) use ($project, $tabContext, $viewState): ?array {
                $tabKey = $item->key;
                $tab = $item->tab;
                $panel = $tab->panel();

                if (! $panel instanceof ProjectTabPanel) {
                    return null;
                }

                $resolved = $panel->resolve($tabKey, $project, $tabContext, $viewState);
                if (! is_array($resolved)) {
                    return null;
                }

                return [
                    'tab' => $tabKey,
                    'component' => $resolved['component'],
                    'props' => $resolved['props'],
                    'key' => $resolved['key'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabs(Project $project, ?User $user): array
    {
        return collect($this->visibleTabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->all();
    }

    /**
     * @return array<int, ProjectTabViewItem>
     */
    public function tabItems(Project $project, ?User $user): array
    {
        $cacheKey = $this->tabItemsCacheKey($project, $user);
        if (array_key_exists($cacheKey, $this->tabItemsCache)) {
            return $this->tabItemsCache[$cacheKey];
        }

        $tabs = $this->tabs();

        if (! $user instanceof User) {
            $overview = $tabs['overview'] ?? null;

            if (! $overview instanceof ResolvedProjectTab) {
                return [];
            }

            $this->tabItemsCache[$cacheKey] = [
                ProjectTabViewItem::fromResolvedTab($overview),
            ];

            return $this->tabItemsCache[$cacheKey];
        }

        $accessibleTabs = collect($tabs)
            ->filter(fn (ResolvedProjectTab $tab): bool => $tab->isVisible($user, $project) === true);

        $preferences = $this->preferencesByTab($user, $accessibleTabs->keys()->all(), $project);

        // If any user preferences exist for this user/project, treat those
        // preferences as authoritative for ordering. Tabs without an explicit
        // preference should be placed after the user-ordered tabs and then
        // ordered by their configured sort value.
        $hasPreferences = $preferences->isNotEmpty();

        $itemsCollection = $accessibleTabs
            ->map(function (ResolvedProjectTab $tab, string $tabKey) use ($preferences, $hasPreferences): ProjectTabViewItem {
                /** @var ProjectTabUserPreference|null $preference */
                $preference = $preferences->get($tabKey);

                if ($preference !== null) {
                    $sort = $preference->sort_order;
                } elseif ($hasPreferences) {
                    // Ensure non-preference tabs come after user-ordered tabs.
                    $sort = 100000 + $tab->sort();
                } else {
                    $sort = $tab->sort();
                }

                return ProjectTabViewItem::fromResolvedTab(
                    $tab,
                    sort: $sort,
                    isHidden: $tabKey === 'overview' ? false : (bool) ($preference?->is_hidden ?? false),
                );
            });

        // Log assigned sorts for debugging before applying the final sort.
        // Debug logging removed in cleanup.

        $items = $itemsCollection->values()->all();

        usort($items, static function (ProjectTabViewItem $a, ProjectTabViewItem $b): int {
            if ($a->sort === $b->sort) {
                return strcmp($a->label, $b->label);
            }

            return $a->sort <=> $b->sort;
        });

        $this->tabItemsCache[$cacheKey] = $items;

        return $this->tabItemsCache[$cacheKey];
    }

    /**
     * @return array<int, ProjectTabViewItem>
     */
    public function visibleTabItems(Project $project, ?User $user): array
    {
        return collect($this->tabItems($project, $user))
            ->reject(static fn (ProjectTabViewItem $item): bool => $item->isHidden)
            ->values()
            ->all();
    }

    /**
     * @return array<int, ProjectTabViewItem>
     */
    public function hiddenTabItems(Project $project, ?User $user): array
    {
        return collect($this->tabItems($project, $user))
            ->filter(static fn (ProjectTabViewItem $item): bool => $item->isHidden)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $orderedVisibleKeys
     */
    public function updateUserTabOrder(User $user, Project $project, array $orderedVisibleKeys): void
    {
        $visibleKeys = collect($this->visibleTabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->all();

        $hiddenKeys = collect($this->hiddenTabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->all();

        // Normalize incoming keys: some front-end sortable libraries send DOM keys
        // or prefixed keys (e.g. 'project-tab-sort-item-{tabKey}'). Try to map
        // those back to the canonical tab keys expected by the registry.
        $normalizedOrdered = [];
        foreach ($orderedVisibleKeys as $incomingKey) {
            if (in_array($incomingKey, $visibleKeys, true)) {
                $normalizedOrdered[] = $incomingKey;
                continue;
            }

            // Try to find a visible key that appears inside the incoming key
            $found = null;
            foreach ($visibleKeys as $vk) {
                if (str_contains($incomingKey, $vk) || str_contains($vk, $incomingKey)) {
                    $found = $vk;
                    break;
                }
            }

            if ($found !== null) {
                $normalizedOrdered[] = $found;
            }
        }

        $orderedVisibleKeys = array_values(array_filter(
            array_values(array_unique($normalizedOrdered)),
            static fn (string $tabKey): bool => in_array($tabKey, $visibleKeys, true)
        ));

        $remainingVisibleKeys = array_values(array_diff($visibleKeys, $orderedVisibleKeys));

        try {
            \Illuminate\Support\Facades\Log::debug('Updating user tab order', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'incoming_order' => $orderedVisibleKeys,
                'current_visible' => $visibleKeys,
                'remaining' => $remainingVisibleKeys,
                'hidden' => $hiddenKeys,
            ]);

            $this->persistUserPreferences(
                $user,
                array_merge($orderedVisibleKeys, $remainingVisibleKeys),
                $hiddenKeys,
                $project,
            );

            \Illuminate\Support\Facades\Log::debug('User tab order updated successfully', [
                'user_id' => $user->id,
                'project_id' => $project->id,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed updating user tab order', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setUserTabHidden(User $user, Project $project, string $tabKey, bool $isHidden): void
    {
        if ($tabKey === 'overview') {
            return;
        }

        $allTabKeys = collect($this->tabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->all();

        if (! in_array($tabKey, $allTabKeys, true)) {
            return;
        }

        $visibleKeys = collect($this->visibleTabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->reject(fn (string $visibleTabKey): bool => $visibleTabKey === $tabKey)
            ->values()
            ->all();

        $hiddenKeys = collect($this->hiddenTabItems($project, $user))
            ->map(static fn (ProjectTabViewItem $item): string => $item->key)
            ->reject(fn (string $hiddenTabKey): bool => $hiddenTabKey === $tabKey)
            ->values()
            ->all();

        if ($isHidden) {
            $hiddenKeys[] = $tabKey;
        } else {
            $visibleKeys[] = $tabKey;
        }

        $this->persistUserPreferences($user, $visibleKeys, $hiddenKeys, $project);
    }

    public function modeQueryParam(string $tab): ?string
    {
        $resolvedTab = $this->tabs()[$tab] ?? null;

        return $resolvedTab instanceof ResolvedProjectTab
            ? $resolvedTab->modeQueryParam()
            : null;
    }

    public function detailQueryParam(string $tab): ?string
    {
        $resolvedTab = $this->tabs()[$tab] ?? null;

        return $resolvedTab instanceof ResolvedProjectTab
            ? $resolvedTab->detailQueryParam()
            : null;
    }

    /**
     * @return array<string, int>
     */
    public function tabBadges(Project $project, ?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $badges = [];
        $tabs = $this->tabs();

        foreach ($this->visibleTabs($project, $user) as $tabKey) {
            $tab = $tabs[$tabKey] ?? null;
            if (! $tab instanceof ResolvedProjectTab) {
                continue;
            }

            $count = $tab->badgeCount($user, $project);
            if (! is_int($count)) {
                continue;
            }

            $badges[$tabKey] = $count;
        }

        return $badges;
    }

    public function isCreateMode(string $tab, Request $request): bool
    {
        $modeQueryParam = $this->modeQueryParam($tab);

        if (! is_string($modeQueryParam)) {
            return false;
        }

        return (string) $request->query($modeQueryParam, '') === 'create';
    }

    /**
     * @param  array<int, string>  $tabKeys
     * @return Collection<string, ProjectTabUserPreference>
     */
    private function preferencesByTab(User $user, array $tabKeys, Project $project): Collection
    {
        if ($tabKeys === []) {
            return collect();
        }

        sort($tabKeys);
        $cacheKey = (string) $user->id.'|'.(string) $project->id.'|'.implode(',', $tabKeys);

        if (array_key_exists($cacheKey, $this->preferencesCache)) {
            return $this->preferencesCache[$cacheKey];
        }

        $prefs = $this->preferenceStore->loadPreferences($user, $tabKeys, $project);

        // Log the loaded preferences for debugging ordering issues.
        // Debug logging removed in cleanup.

        $this->preferencesCache[$cacheKey] = $prefs;

        return $this->preferencesCache[$cacheKey];
    }

    /**
     * @param  array<int, string>  $visibleKeys
     * @param  array<int, string>  $hiddenKeys
     */
    private function persistUserPreferences(User $user, array $visibleKeys, array $hiddenKeys, ?Project $project = null): void
    {
        $this->preferenceStore->persist($user, $visibleKeys, $hiddenKeys, $project);

        $this->flushRuntimeCaches();
    }

    private function projectTabDefinitionsTableExists(): bool
    {
        if (is_bool($this->hasProjectTabDefinitionsTable)) {
            return $this->hasProjectTabDefinitionsTable;
        }

        $this->hasProjectTabDefinitionsTable = Schema::hasTable('project_tab_definitions');

        return $this->hasProjectTabDefinitionsTable;
    }

    private function tabItemsCacheKey(Project $project, ?User $user): string
    {
        return (string) $project->id.'|'.($user instanceof User ? (string) $user->id : 'guest');
    }

    private function flushRuntimeCaches(): void
    {
        $this->preferencesCache = [];
        $this->tabItemsCache = [];
    }
}
