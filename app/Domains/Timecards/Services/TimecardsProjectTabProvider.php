<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Projects\Support\ProjectTab;

class TimecardsProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'time',
                label: 'Time',
                sort: 110,
                modeParam: null,
                detailQueryParam: null,
                panel: null,
                badgeResolver: static fn (User $user, Project $project): ?int => app(ProjectTimecardMetricsService::class)
                    ->summaryForProject((string) $project->id)['time_entry_count'] ?? 0,
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAny', Timecard::class),
            ),
        ];
    }
}
