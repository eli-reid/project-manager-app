<?php

namespace App\Domains\Timecards\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;

final class TimecardsProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'time',
            label: 'Time',
            sort: 110,
            panel: new LivewireComponentTabPanel(
                component: 'timecards::admin.projects.timecard-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', Timecard::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return app(ProjectTimecardMetricsService::class)
            ->summaryForProject((string) $project->id)['time_entry_count'] ?? 0;
    }
}
