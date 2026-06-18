<?php

namespace App\Domains\Dailies\Services;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;

class DailiesProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'dailies',
                label: 'Dailies',
                sort: 20,
                detailQueryParam: 'dailyId',
                badgeResolver: static fn (User $user, Project $project): ?int => $project->dailyReports()->count(),
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAll', DailyReport::class),
            ),
        ];
    }
}
