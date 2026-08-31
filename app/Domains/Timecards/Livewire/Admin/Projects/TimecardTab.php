<?php

namespace App\Domains\Timecards\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class TimecardTab extends Component
{
    public Project $project;

    public ?string $quickAddUserId = null;

    public string $quickAddDate = '';

    public string $quickAddHours = '0.00';

    public ?string $quickAddNotes = null;

    private ProjectTimecardMetricsService $metricsService;

    private TimecardLifecycleService $timecardLifecycleService;

    public function boot(ProjectTimecardMetricsService $metricsService, TimecardLifecycleService $timecardLifecycleService): void
    {
        $this->metricsService = $metricsService;
        $this->timecardLifecycleService = $timecardLifecycleService;
    }

    public function mount(): void
    {
        $this->quickAddUserId = Auth::id();
        $this->quickAddDate = now()->toDateString();
    }

    public function addProjectHours(): void
    {
        Gate::authorize('create', Timecard::class);

        $validated = $this->validate([
            'quickAddUserId' => ['required', 'exists:users,id'],
            'quickAddDate' => ['required', 'date'],
            'quickAddHours' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'quickAddNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::query()->findOrFail((string) $validated['quickAddUserId']);

        $this->timecardLifecycleService->addSingleProjectEntry(
            $user,
            (string) $this->project->id,
            (string) $validated['quickAddDate'],
            (float) $validated['quickAddHours'],
            $validated['quickAddNotes'] ?? null,
        );

        $this->quickAddHours = '0.00';
        $this->quickAddNotes = null;

        session()->flash('success', 'Project hours added successfully.');
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
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
