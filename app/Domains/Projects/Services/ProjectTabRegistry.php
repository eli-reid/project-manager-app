<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabInterface;
use App\Domains\Projects\Contracts\ProjectTabPanelInterface;
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
     * @param  array<int, class-string<ProjectTabInterface>>  $definitions
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

                if (! $panel instanceof ProjectTabPanelInterface) {
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

        $preferences = $this->preferencesByTab($user, $accessibleTabs->keys()->all());

        $items = $accessibleTabs
            ->map(function (ResolvedProjectTab $tab, string $tabKey) use ($preferences): ProjectTabViewItem {
                /** @var ProjectTabUserPreference|null $preference */
                $preference = $preferences->get($tabKey);

                return ProjectTabViewItem::fromResolvedTab(
                    $tab,
                    sort: $preference?->sort_order ?? $tab->sort(),
                    isHidden: $tabKey === 'overview' ? false : (bool) ($preference?->is_hidden ?? false),
                );
            })
            ->sortBy([
                [static fn (ProjectTabViewItem $item): int => $item->sort, 'asc'],
                [static fn (ProjectTabViewItem $item): string => $item->label, 'asc'],
            ])
            ->values()
            ->all();

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

        $orderedVisibleKeys = array_values(array_filter(
            $orderedVisibleKeys,
            static fn (string $tabKey): bool => in_array($tabKey, $visibleKeys, true)
        ));

        $remainingVisibleKeys = array_values(array_diff($visibleKeys, $orderedVisibleKeys));

        $this->persistUserPreferences(
            $user,
            array_merge($orderedVisibleKeys, $remainingVisibleKeys),
            $hiddenKeys,
        );
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

        $this->persistUserPreferences($user, $visibleKeys, $hiddenKeys);
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
    private function preferencesByTab(User $user, array $tabKeys): Collection
    {
        if ($tabKeys === []) {
            return collect();
        }

        sort($tabKeys);
        $cacheKey = (string) $user->id.'|'.implode(',', $tabKeys);

        if (array_key_exists($cacheKey, $this->preferencesCache)) {
            return $this->preferencesCache[$cacheKey];
        }

        $this->preferencesCache[$cacheKey] = $this->preferenceStore->loadPreferences($user, $tabKeys);

        return $this->preferencesCache[$cacheKey];
    }

    /**
     * @param  array<int, string>  $visibleKeys
     * @param  array<int, string>  $hiddenKeys
     */
    private function persistUserPreferences(User $user, array $visibleKeys, array $hiddenKeys): void
    {
        $this->preferenceStore->persist($user, $visibleKeys, $hiddenKeys);

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
