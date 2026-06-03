<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Http\Request;

class ProjectTabRegistry
{
    /**
     * @var array<string, array{key:string,label:string,sort:int,mode_param:string|null,is_visible:callable(User, Project): bool}>
     */
    private array $registeredDefinitions = [];

    /**
     * @return array<string, array{label:string,mode_param?:string,is_visible:callable(User, Project): bool}>
     */
    public function definitions(): array
    {
        $definitions = $this->registeredDefinitions;
        if ($definitions === []) {
            $definitions = $this->fallbackDefinitions();
        }

        $overrides = ProjectTabDefinition::query()
            ->whereIn('key', array_keys($definitions))
            ->get()
            ->keyBy('key');

        $resolvedDefinitions = [];
        foreach ($definitions as $tabKey => $definition) {
            $override = $overrides->get($tabKey);

            $label = $definition['label'];
            $modeParam = $definition['mode_param'];
            $sortOrder = $definition['sort'];
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
                'sort' => $sortOrder,
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
                'sort' => $fallback['sort'],
                'is_visible' => $fallback['is_visible'],
            ]] + $resolvedDefinitions;
        }

        return $resolvedDefinitions;
    }

    /**
     * @param  array<int, array{key:string,label?:string,sort?:int,mode_param?:string|null,is_visible?:callable(User, Project): bool}>  $definitions
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
            $isVisible = $definition['is_visible'] ?? static fn (User $user, Project $project): bool => false;

            $this->registeredDefinitions[$key] = [
                'key' => $key,
                'label' => $label,
                'sort' => $sort,
                'mode_param' => is_string($modeParam) && $modeParam !== '' ? $modeParam : null,
                'is_visible' => $isVisible,
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabs(Project $project, ?User $user): array
    {
        if (! $user instanceof User) {
            return ['overview'];
        }

        $tabs = [];
        foreach ($this->definitions() as $tabKey => $definition) {
            $isVisible = $definition['is_visible'];

            if ($isVisible($user, $project) === true) {
                $tabs[] = $tabKey;
            }
        }

        return $tabs;
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

    public function isCreateMode(string $tab, Request $request): bool
    {
        $modeQueryParam = $this->modeQueryParam($tab);

        if (! is_string($modeQueryParam)) {
            return false;
        }

        return (string) $request->query($modeQueryParam, '') === 'create';
    }

    /**
     * @return array<string, array{key:string,label:string,sort:int,mode_param:string|null,is_visible:callable(User, Project): bool}>
     */
    private function fallbackDefinitions(): array
    {
        return [
            'overview' => [
                'key' => 'overview',
                'label' => 'Overview',
                'sort' => 10,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => true,
            ],
            'dailies' => [
                'key' => 'dailies',
                'label' => 'Dailies',
                'sort' => 20,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAll', DailyReport::class),
            ],
            'tasks' => [
                'key' => 'tasks',
                'label' => 'Tasks',
                'sort' => 30,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('tasks.view')
                    || $user->hasPermission('task-categories.view'),
            ],
            'invoices' => [
                'key' => 'invoices',
                'label' => 'Invoices',
                'sort' => 40,
                'mode_param' => 'invoiceMode',
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Invoice::class),
            ],
            'stock' => [
                'key' => 'stock',
                'label' => 'Stock',
                'sort' => 50,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', StockOrder::class),
            ],
            'submittals' => [
                'key' => 'submittals',
                'label' => 'Submittals',
                'sort' => 60,
                'mode_param' => 'submittalMode',
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Submittal::class)
                    || $user->can('create', Submittal::class),
            ],
            'change-orders' => [
                'key' => 'change-orders',
                'label' => 'Change Orders',
                'sort' => 70,
                'mode_param' => 'changeOrderMode',
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', ChangeOrder::class),
            ],
            'rfis' => [
                'key' => 'rfis',
                'label' => 'RFIs',
                'sort' => 80,
                'mode_param' => 'rfiMode',
                'is_visible' => static fn (User $user, Project $project): bool => $user->hasPermission('rfis.view-any')
                    || $user->hasPermission('rfis.view')
                    || $user->hasPermission('rfis.create'),
            ],
            'documents' => [
                'key' => 'documents',
                'label' => 'Library',
                'sort' => 90,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Document::class),
            ],
            'access' => [
                'key' => 'access',
                'label' => 'Access',
                'sort' => 100,
                'mode_param' => null,
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
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Timecard::class),
            ],
            'financials' => [
                'key' => 'financials',
                'label' => 'Financials',
                'sort' => 120,
                'mode_param' => null,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewFinancials', $project),
            ],
        ];
    }
}
