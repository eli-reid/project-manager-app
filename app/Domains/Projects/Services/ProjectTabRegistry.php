<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Projects\Support\ProjectTabs\DailiesTabPanel;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\RFIs\Models\RFI;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProjectTabRegistry
{
    /**
     * @var array<string, array{key:string,label:string,sort:int,mode_param:string|null,detail_query_param:string|null,panel:?ProjectTabPanel,badge_count:callable(User, Project): int|null,is_visible:callable(User, Project): bool}>
     */
    private array $registeredDefinitions = [];

    /**
     * @var array<string, array{label:string,mode_param:string|null,detail_query_param:string|null,panel:?ProjectTabPanel,sort:int,badge_count:callable(User, Project): int|null,is_visible:callable(User, Project): bool}>|null
     */
    private ?array $resolvedDefinitions = null;

    /**
     * @var array<string, Collection<string, ProjectTabUserPreference>>
     */
    private array $preferencesCache = [];

    /**
     * @var array<string, array<int, array{key:string,label:string,mode_param:string|null,detail_query_param:string|null,sort:int,is_hidden:bool}>>
     */
    private array $tabItemsCache = [];

    private ?bool $hasProjectTabDefinitionsTable = null;

    private ?bool $hasProjectTabUserPreferencesTable = null;

    /**
     * @return array<string, array{label:string,mode_param:string|null,detail_query_param:string|null,panel:?ProjectTabPanel,sort:int,badge_count:callable(User, Project): int|null,is_visible:callable(User, Project): bool}>
     */
    public function definitions(): array
    {
        if (is_array($this->resolvedDefinitions)) {
            return $this->resolvedDefinitions;
        }

        $definitions = $this->registeredDefinitions;
        if ($definitions === []) {
            $definitions = $this->fallbackDefinitions();
        }

        $overrides = $this->projectTabDefinitionsTableExists()
            ? ProjectTabDefinition::query()
                ->whereIn('key', array_keys($definitions))
                ->get()
                ->keyBy('key')
            : collect();

        $resolvedDefinitions = [];
        foreach ($definitions as $tabKey => $definition) {
            $override = $overrides->get($tabKey);

            $label = $definition['label'];
            $modeParam = $definition['mode_param'];
            $detailQueryParam = $definition['detail_query_param'];
            $panel = $definition['panel'] ?? null;
            $sortOrder = $definition['sort'];
            $badgeCount = $definition['badge_count'];
            $isActive = true;

            if ($override instanceof ProjectTabDefinition) {
                $label = (string) ($override->label ?: $label);
                $modeParam = $override->mode_query_param;
                $sortOrder = (int) $override->sort_order;
                $isActive = (bool) $override->is_active;
            }

            if (! $isActive) {
                continue;
            }

            $resolvedDefinitions[$tabKey] = [
                'label' => $label,
                'mode_param' => $modeParam,
                'detail_query_param' => $detailQueryParam,
                'panel' => $panel,
                'sort' => $sortOrder,
                'badge_count' => $badgeCount,
                'is_visible' => $definition['is_visible'],
            ];
        }

        uasort($resolvedDefinitions, static function (array $left, array $right): int {
            if ($left['sort'] === $right['sort']) {
                return strcmp($left['label'], $right['label']);
            }

            return $left['sort'] <=> $right['sort'];
        });

        if (! array_key_exists('overview', $resolvedDefinitions)) {
            $fallback = $this->fallbackDefinitions()['overview'];
            $resolvedDefinitions = ['overview' => [
                'label' => $fallback['label'],
                'mode_param' => $fallback['mode_param'],
                'detail_query_param' => $fallback['detail_query_param'],
                'panel' => $fallback['panel'],
                'sort' => $fallback['sort'],
                'badge_count' => $fallback['badge_count'],
                'is_visible' => $fallback['is_visible'],
            ]] + $resolvedDefinitions;
        }

        $this->resolvedDefinitions = $resolvedDefinitions;

        return $this->resolvedDefinitions;
    }

    /**
     * @param  array<int, array{key:string,label?:string,sort?:int,mode_param?:string|null,detail_query_param?:string|null,panel?:ProjectTabPanel|class-string<ProjectTabPanel>|null,badge_count?:callable(User, Project): int|null,is_visible?:callable(User, Project): bool}>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $label = (string) ($definition['label'] ?? str($key)->replace(['-', '_', '.'], ' ')->headline()->value());
            $sort = (int) ($definition['sort'] ?? 100);
            $modeParam = $definition['mode_param'] ?? null;
            $detailQueryParam = $definition['detail_query_param'] ?? null;
            $panel = $definition['panel'] ?? null;
            $badgeCount = $definition['badge_count'] ?? null;
            $isVisible = $definition['is_visible'] ?? static fn (User $user, Project $project): bool => false;

            if (is_string($panel) && class_exists($panel) && is_subclass_of($panel, ProjectTabPanel::class)) {
                $panel = app($panel);
            }

            if (! $panel instanceof ProjectTabPanel) {
                $panel = $this->defaultPanelFor($key);
            }

            $this->registeredDefinitions[$key] = [
                'key' => $key,
                'label' => $label,
                'sort' => $sort,
                'mode_param' => is_string($modeParam) && $modeParam !== '' ? $modeParam : null,
                'detail_query_param' => is_string($detailQueryParam) && $detailQueryParam !== '' ? $detailQueryParam : null,
                'panel' => $panel,
                'badge_count' => is_callable($badgeCount) ? $badgeCount : static fn (): ?int => null,
                'is_visible' => $isVisible,
            ];
        }

        $this->resolvedDefinitions = null;
        $this->tabItemsCache = [];
    }

    /**
     * @param  array<string, array{modeParam:string,mode:string,detailParam:?string,detailId:string,isCreateMode:bool}>  $tabContext
     * @param  array<string, mixed>  $viewState
     * @return array<int, array{tab:string,component:string,props:array<string, mixed>,key:string}>
     */
    public function tabPanels(Project $project, ?User $user, array $tabContext = [], array $viewState = []): array
    {
        $definitions = $this->definitions();

        return collect($this->visibleTabItems($project, $user))
            ->map(function (array $item) use ($definitions, $project, $tabContext, $viewState): ?array {
                $tabKey = $item['key'];
                $definition = $definitions[$tabKey] ?? null;

                if (! is_array($definition)) {
                    return null;
                }

                $panel = $definition['panel'] ?? null;
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
            ->pluck('key')
            ->all();
    }

    /**
     * @return array<int, array{key:string,label:string,mode_param:string|null,detail_query_param:string|null,sort:int,is_hidden:bool}>
     */
    public function tabItems(Project $project, ?User $user): array
    {
        $cacheKey = $this->tabItemsCacheKey($project, $user);
        if (array_key_exists($cacheKey, $this->tabItemsCache)) {
            return $this->tabItemsCache[$cacheKey];
        }

        if (! $user instanceof User) {
            $overview = $this->definitions()['overview'] ?? null;

            if (! is_array($overview)) {
                return [];
            }

            $this->tabItemsCache[$cacheKey] = [[
                'key' => 'overview',
                'label' => $overview['label'],
                'mode_param' => $overview['mode_param'],
                'detail_query_param' => $overview['detail_query_param'],
                'sort' => $overview['sort'],
                'is_hidden' => false,
            ]];

            return $this->tabItemsCache[$cacheKey];
        }

        $accessibleDefinitions = collect($this->definitions())
            ->filter(function (array $definition) use ($project, $user): bool {
                $isVisible = $definition['is_visible'];

                return $isVisible($user, $project) === true;
            });

        $preferences = $this->preferencesByTab($user, $accessibleDefinitions->keys()->all());

        $items = $accessibleDefinitions
            ->map(function (array $definition, string $tabKey) use ($preferences): array {
                /** @var ProjectTabUserPreference|null $preference */
                $preference = $preferences->get($tabKey);

                return [
                    'key' => $tabKey,
                    'label' => $definition['label'],
                    'mode_param' => $definition['mode_param'],
                    'detail_query_param' => $definition['detail_query_param'],
                    'sort' => $preference?->sort_order ?? $definition['sort'],
                    'is_hidden' => $tabKey === 'overview' ? false : (bool) ($preference?->is_hidden ?? false),
                ];
            })
            ->sortBy([
                ['sort', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();

        $this->tabItemsCache[$cacheKey] = $items;

        return $this->tabItemsCache[$cacheKey];
    }

    /**
     * @return array<int, array{key:string,label:string,mode_param:string|null,detail_query_param:string|null,sort:int,is_hidden:bool}>
     */
    public function visibleTabItems(Project $project, ?User $user): array
    {
        return collect($this->tabItems($project, $user))
            ->reject(fn (array $item): bool => $item['is_hidden'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key:string,label:string,mode_param:string|null,detail_query_param:string|null,sort:int,is_hidden:bool}>
     */
    public function hiddenTabItems(Project $project, ?User $user): array
    {
        return collect($this->tabItems($project, $user))
            ->filter(fn (array $item): bool => $item['is_hidden'])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $orderedVisibleKeys
     */
    public function updateUserTabOrder(User $user, Project $project, array $orderedVisibleKeys): void
    {
        $visibleKeys = collect($this->visibleTabItems($project, $user))
            ->pluck('key')
            ->all();

        $hiddenKeys = collect($this->hiddenTabItems($project, $user))
            ->pluck('key')
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
            ->pluck('key')
            ->all();

        if (! in_array($tabKey, $allTabKeys, true)) {
            return;
        }

        $visibleKeys = collect($this->visibleTabItems($project, $user))
            ->pluck('key')
            ->reject(fn (string $visibleTabKey): bool => $visibleTabKey === $tabKey)
            ->values()
            ->all();

        $hiddenKeys = collect($this->hiddenTabItems($project, $user))
            ->pluck('key')
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
        $definition = $this->definitions()[$tab] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        $modeParam = $definition['mode_param'] ?? null;

        return is_string($modeParam) && $modeParam !== ''
            ? $modeParam
            : null;
    }

    public function detailQueryParam(string $tab): ?string
    {
        $definition = $this->definitions()[$tab] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        $detailQueryParam = $definition['detail_query_param'] ?? null;

        return is_string($detailQueryParam) && $detailQueryParam !== ''
            ? $detailQueryParam
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

        foreach ($this->visibleTabs($project, $user) as $tabKey) {
            $definition = $this->definitions()[$tabKey] ?? null;
            if (! is_array($definition)) {
                continue;
            }

            $resolver = $definition['badge_count'] ?? null;
            if (! is_callable($resolver)) {
                continue;
            }

            $count = $resolver($user, $project);
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
     * @return array<string, array{key:string,label:string,sort:int,mode_param:string|null,detail_query_param:string|null,panel:?ProjectTabPanel,badge_count:callable(User, Project): int|null,is_visible:callable(User, Project): bool}>
     */
    private function fallbackDefinitions(): array
    {
        return [
            'overview' => [
                'key' => 'overview',
                'label' => 'Overview',
                'sort' => 10,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => null,
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => true,
            ],
            'dailies' => [
                'key' => 'dailies',
                'label' => 'Dailies',
                'sort' => 20,
                'mode_param' => null,
                'detail_query_param' => 'dailyId',
                'panel' => $this->defaultPanelFor('dailies'),
                'badge_count' => static fn (User $user, Project $project): ?int => $project->dailyReports()->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAll', DailyReport::class),
            ],
            'tasks' => [
                'key' => 'tasks',
                'label' => 'Tasks',
                'sort' => 30,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => $this->defaultPanelFor('tasks'),
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('tasks.view')
                    || $user->hasPermission('task-categories.view'),
            ],
            'invoices' => [
                'key' => 'invoices',
                'label' => 'Invoices',
                'sort' => 40,
                'mode_param' => 'invoiceMode',
                'detail_query_param' => 'invoiceId',
                'panel' => $this->defaultPanelFor('invoices'),
                'badge_count' => static fn (User $user, Project $project): ?int => Invoice::query()
                    ->where('project_id', $project->id)
                    ->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Invoice::class),
            ],
            'stock' => [
                'key' => 'stock',
                'label' => 'Stock',
                'sort' => 50,
                'mode_param' => null,
                'detail_query_param' => 'stockOrderId',
                'panel' => $this->defaultPanelFor('stock'),
                'badge_count' => static fn (User $user, Project $project): ?int => StockOrder::query()
                    ->where('project_id', $project->id)
                    ->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', StockOrder::class),
            ],
            'submittals' => [
                'key' => 'submittals',
                'label' => 'Submittals',
                'sort' => 60,
                'mode_param' => 'submittalMode',
                'detail_query_param' => 'submittalId',
                'panel' => $this->defaultPanelFor('submittals'),
                'badge_count' => static fn (User $user, Project $project): ?int => $user->can('viewAny', Submittal::class)
                    ? Submittal::query()->where('project_id', $project->id)->count()
                    : 0,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Submittal::class)
                    || $user->can('create', Submittal::class),
            ],
            'change-orders' => [
                'key' => 'change-orders',
                'label' => 'Change Orders',
                'sort' => 70,
                'mode_param' => 'changeOrderMode',
                'detail_query_param' => 'changeOrderId',
                'panel' => $this->defaultPanelFor('change-orders'),
                'badge_count' => static fn (User $user, Project $project): ?int => ChangeOrder::query()
                    ->where('project_id', $project->id)
                    ->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', ChangeOrder::class),
            ],
            'rfis' => [
                'key' => 'rfis',
                'label' => 'RFIs',
                'sort' => 80,
                'mode_param' => 'rfiMode',
                'detail_query_param' => 'rfiId',
                'panel' => $this->defaultPanelFor('rfis'),
                'badge_count' => static fn (User $user, Project $project): ?int => RFI::query()
                    ->where('project_id', $project->id)
                    ->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('rfis.view-any')
                    || $user->hasPermission('rfis.view')
                    || $user->hasPermission('rfis.create'),
            ],
            'documents' => [
                'key' => 'documents',
                'label' => 'Library',
                'sort' => 90,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => $this->defaultPanelFor('documents'),
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Document::class),
            ],
            'access' => [
                'key' => 'access',
                'label' => 'Access',
                'sort' => 100,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => $this->defaultPanelFor('access'),
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('project-access.view')
                    || $user->hasPermission('project-access.grant')
                    || $user->hasPermission('project-access.revoke')
                    || $user->hasPermission('project-access.manage'),
            ],
            'time' => [
                'key' => 'time',
                'label' => 'Time',
                'sort' => 110,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => $this->defaultPanelFor('time'),
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Timecard::class),
            ],
            'financials' => [
                'key' => 'financials',
                'label' => 'Financials',
                'sort' => 120,
                'mode_param' => null,
                'detail_query_param' => null,
                'panel' => $this->defaultPanelFor('financials'),
                'badge_count' => static fn (): ?int => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewFinancials', $project),
            ],
        ];
    }

    private function defaultPanelFor(string $tabKey): ?ProjectTabPanel
    {
        return match ($tabKey) {
            'dailies' => new DailiesTabPanel,
            'tasks' => new LivewireComponentTabPanel(
                component: 'tasks::admin.projects.task-hierarchy-widget',
                baseProps: [],
                keyPattern: 'project-task-widget-{projectId}-{taskWidgetVersion}',
            ),
            'invoices' => new LivewireComponentTabPanel(
                component: 'invoices::admin.invoices.index',
                baseProps: ['embedded' => true],
            ),
            'stock' => new LivewireComponentTabPanel(
                component: 'stock::admin.stock-orders.index',
                baseProps: ['embedded' => true],
            ),
            'submittals' => new LivewireComponentTabPanel(
                component: 'submittals::admin.submittals.index',
                baseProps: ['embedded' => true],
                modeProp: 'mode',
                detailProp: 'submittalId',
            ),
            'change-orders' => new LivewireComponentTabPanel(
                component: 'change-orders::admin.change-orders.index',
                baseProps: ['embedded' => true],
                modeProp: 'mode',
                detailProp: 'changeOrderId',
            ),
            'rfis' => new LivewireComponentTabPanel(
                component: 'App\\Domains\\RFIs\\Livewire\\Admin\\RFIs\\Index',
                baseProps: ['embedded' => true],
                createModeProp: 'isCreateMode',
                appendCreateSuffix: true,
            ),
            'documents' => new LivewireComponentTabPanel(
                component: 'documents::admin.projects.documents-tab',
            ),
            'access' => new LivewireComponentTabPanel(
                component: 'projects::admin.projects.access-tab',
            ),
            'time' => new LivewireComponentTabPanel(
                component: 'timecards::admin.projects.timecard-tab',
            ),
            'financials' => new LivewireComponentTabPanel(
                component: 'projects::admin.projects.financials-tab',
            ),
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $tabKeys
     * @return Collection<string, ProjectTabUserPreference>
     */
    private function preferencesByTab(User $user, array $tabKeys): Collection
    {
        if ($tabKeys === [] || ! $this->projectTabUserPreferencesTableExists()) {
            return collect();
        }

        sort($tabKeys);
        $cacheKey = (string) $user->id.'|'.implode(',', $tabKeys);

        if (array_key_exists($cacheKey, $this->preferencesCache)) {
            return $this->preferencesCache[$cacheKey];
        }

        $this->preferencesCache[$cacheKey] = ProjectTabUserPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('tab_key', $tabKeys)
            ->get()
            ->keyBy('tab_key');

        return $this->preferencesCache[$cacheKey];
    }

    /**
     * @param  array<int, string>  $visibleKeys
     * @param  array<int, string>  $hiddenKeys
     */
    private function persistUserPreferences(User $user, array $visibleKeys, array $hiddenKeys): void
    {
        if (! $this->projectTabUserPreferencesTableExists()) {
            return;
        }

        $rows = [];
        $sortOrder = 1;
        $timestamp = now();

        foreach ($visibleKeys as $tabKey) {
            $rows[] = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        foreach ($hiddenKeys as $tabKey) {
            $rows[] = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows === []) {
            return;
        }

        ProjectTabUserPreference::query()->upsert(
            $rows,
            ['user_id', 'tab_key'],
            ['sort_order', 'is_hidden', 'updated_at'],
        );

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

    private function projectTabUserPreferencesTableExists(): bool
    {
        if (is_bool($this->hasProjectTabUserPreferencesTable)) {
            return $this->hasProjectTabUserPreferencesTable;
        }

        $this->hasProjectTabUserPreferencesTable = Schema::hasTable('project_tab_user_preferences');

        return $this->hasProjectTabUserPreferencesTable;
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
