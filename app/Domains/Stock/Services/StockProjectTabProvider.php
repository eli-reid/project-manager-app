<?php

namespace App\Domains\Stock\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Projects\Support\ProjectTab;

class StockProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'stock',
                label: 'Stock',
                sort: 50,
                modeParam: null,
                detailQueryParam: 'stockOrderId',
                panel: null,
                badgeResolver: static fn (User $user, Project $project): ?int => StockOrder::query()
                    ->where('project_id', $project->id)
                    ->count(),
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAny', StockOrder::class),
            ),
        ];
    }
}
