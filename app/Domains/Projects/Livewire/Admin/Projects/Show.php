<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Project Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        if (! in_array($this->activeTab, $this->tabs(), true)) {
            $this->activeTab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, $this->tabs(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * @return array<int, string>
     */
    protected function tabs(): array
    {
        $tabs = ['overview'];
        $user = Auth::user();

        if ($user?->can('viewAll', DailyReport::class)) {
            $tabs[] = 'dailies';
        }

        if ($user?->hasPermission('tasks.view') || $user?->hasPermission('task-categories.view')) {
            $tabs[] = 'tasks';
        }

        if ($user?->can('viewAny', Invoice::class)) {
            $tabs[] = 'invoices';
        }

        return $tabs;
    }

    public function render()
    {
        $dailyCount = 0;
        $projectDailies = collect();
        $invoiceCount = 0;
        $projectInvoices = collect();

        if (in_array('dailies', $this->tabs(), true)) {
            $dailyCount = $this->project->dailyReports()->count();

            if ($this->activeTab === 'dailies') {
                $projectDailies = $this->project->dailyReports()
                    ->with(['user', 'submittedBy'])
                    ->latest('report_date')
                    ->limit(15)
                    ->get();
            }
        }

        if (in_array('invoices', $this->tabs(), true)) {
            $invoiceCount = Invoice::query()
                ->where('project_id', $this->project->id)
                ->count();

            if ($this->activeTab === 'invoices') {
                $projectInvoices = Invoice::query()
                    ->where('project_id', $this->project->id)
                    ->latest('invoice_date')
                    ->limit(15)
                    ->get();
            }
        }

        return view('projects::livewire.admin.projects.show', [
            'tabs' => $this->tabs(),
            'dailyCount' => $dailyCount,
            'projectDailies' => $projectDailies,
            'taskCount' => Task::query()->where('project_id', $this->project->id)->count(),
            'invoiceCount' => $invoiceCount,
            'projectInvoices' => $projectInvoices,
        ]);
    }
}
