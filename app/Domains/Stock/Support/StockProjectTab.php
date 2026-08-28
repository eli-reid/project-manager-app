<?php

namespace App\Domains\Stock\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\Stock\Models\StockOrder;

final class StockProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'stock',
            label: 'Stock',
            sort: 50,
            detailQueryParam: 'stockOrderId',
            panel: new LivewireComponentTabPanel(
                component: 'stock::admin.stock-orders.index',
                baseProps: ['embedded' => true],
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', StockOrder::class);
    }

    public function badgeCountRelations(User $user, Project $project): array
    {
        return ['stockOrders'];
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        $count = $project->getAttribute('stock_orders_count');

        return is_numeric($count)
            ? (int) $count
            : $project->stockOrders()->count();
    }
}
