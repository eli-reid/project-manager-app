<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectFinancialsService;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinancialsTab extends Component
{
    public Project $project;

    private ProjectFinancialsService $financialsService;

    private ProjectTimecardMetricsService $timecardMetricsService;

    public function boot(
        ProjectFinancialsService $financialsService,
        ProjectTimecardMetricsService $timecardMetricsService,
    ): void {
        $this->financialsService = $financialsService;
        $this->timecardMetricsService = $timecardMetricsService;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $financialSummary = $this->financialsService->summary($this->project);

        $timecardSummary = null;
        if ($user->hasPermission('timecards.view') || $user->hasPermission('timecards.view-all')) {
            $timecardSummary = $this->timecardMetricsService->summaryForProject((string) $this->project->id);
        }

        return view('projects::livewire.admin.projects.financials-tab', [
            'financialSummary' => $financialSummary,
            'timecardSummary' => $timecardSummary,
        ]);
    }
}
