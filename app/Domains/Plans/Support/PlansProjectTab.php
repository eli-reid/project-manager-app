<?php

declare(strict_types=1);

namespace App\Domains\Plans\Support;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class PlansProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'plans',
            label: 'Plans',
            sort: 95,
            panel: new LivewireComponentTabPanel(component: 'plans::admin.projects.plans-tab'),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', PlanSet::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return PlanSet::query()->whereBelongsTo($project)->where('status', PlanSet::STATUS_READY)->count() ?: null;
    }
}
