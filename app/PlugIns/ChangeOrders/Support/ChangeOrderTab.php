<?php

namespace App\Domains\ChangeOrders\Support;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class ChangeOrderTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'change-orders',
            label: 'Change Orders',
            sort: 70,
            modeParam: 'changeOrderMode',
            detailQueryParam: 'changeOrderId',
            panel: new LivewireComponentTabPanel(
                component: 'change-orders::admin.change-orders.index',
                baseProps: ['embedded' => true],
                modeProp: 'mode',
                detailProp: 'changeOrderId',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', ChangeOrder::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return ChangeOrder::query()
            ->where('project_id', $project->id)
            ->count();
    }
}
