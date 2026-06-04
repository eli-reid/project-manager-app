<?php

namespace App\Domains\Timecards\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;
use Livewire\Component;

class TimecardTab extends Component
{
    public Project $project;

    private ProjectTimecardMetricsService $metricsService;

    public function boot(ProjectTimecardMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    public function render()
    {
        $summary = $this->metricsService->summaryForProject((string) $this->project->id);
        $detail = $this->metricsService->detailForProject((string) $this->project->id);

        return view('timecards::livewire.admin.projects.timecard-tab', [
            'totalHours' => $summary['total_hours'],
            'regularHours' => $summary['regular_hours'],
            'overtimeHours' => $summary['overtime_hours'],
            'doubleTimeHours' => $summary['double_time_hours'],
            'recentTimeEntries' => $detail['recent_time_entries'],
            'hoursByUser' => $detail['hours_by_user'],
        ]);
    }
}
