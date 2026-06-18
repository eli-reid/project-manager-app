<?php

namespace App\Domains\RFIs\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\Projects\Support\ProjectTab;

class RFIsProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'rfis',
                label: 'RFIs',
                sort: 80,
                modeParam: 'rfiMode',
                detailQueryParam: 'rfiId',
                badgeResolver: static fn (User $user, Project $project): ?int => RFI::query()->where('project_id', $project->id)->count(),
                visibilityResolver: static fn (User $user, Project $project): bool => $user->hasPermission('rfis.view-any')
                    || $user->hasPermission('rfis.view')
                    || $user->hasPermission('rfis.create'),
            ),
        ];
    }
}
