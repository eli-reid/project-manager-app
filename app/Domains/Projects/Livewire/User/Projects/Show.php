<?php

namespace App\Domains\Projects\Livewire\User\Projects;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectFinancialsService;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Tasks\Models\Task;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
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

        if ($user?->can('viewAny', DailyReport::class)) {
            $tabs[] = 'dailies';
        }

        if ($user?->can('viewAny', Task::class)) {
            $tabs[] = 'tasks';
        }

        if ($user?->can('viewAny', Invoice::class)) {
            $tabs[] = 'invoices';
        }

        if ($user?->can('viewAny', StockOrder::class) || $user?->hasPermission('stock-orders.view')) {
            $tabs[] = 'stock';
        }

        if ($user?->can('viewAny', Document::class)) {
            $tabs[] = 'documents';
        }

        if ($user?->can('viewAny', Timecard::class)) {
            $tabs[] = 'time';
        }

        if ($user?->can('viewFinancials', $this->project)) {
            $tabs[] = 'financials';
        }

        if ($user?->can('reports.payroll.view')) {
            $tabs[] = 'forecasting';
        }

        return $tabs;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $tabs = $this->tabs();

        $taskCount = 0;
        $recentTasks = collect();
        if (in_array('tasks', $tabs, true)) {
            $taskCount = Task::query()->where('project_id', $this->project->id)->count();

            if ($this->activeTab === 'tasks') {
                $recentTasks = Task::query()
                    ->with(['assignedTo:id,first_name,last_name'])
                    ->where('project_id', $this->project->id)
                    ->latest()
                    ->limit(10)
                    ->get();
            }
        }

        $dailyCount = 0;
        $recentDailies = collect();
        if (in_array('dailies', $tabs, true)) {
            $dailyQuery = DailyReport::query()->where('project_id', $this->project->id);
            if (! $user->can('viewAll', DailyReport::class)) {
                $dailyQuery->where('user_id', $user->id);
            }

            $dailyCount = (clone $dailyQuery)->count();

            if ($this->activeTab === 'dailies') {
                $recentDailies = $dailyQuery
                    ->with(['user:id,first_name,last_name'])
                    ->latest('report_date')
                    ->limit(10)
                    ->get();
            }
        }

        $invoiceCount = 0;
        $recentInvoices = collect();
        if (in_array('invoices', $tabs, true)) {
            $invoiceCount = Invoice::query()->where('project_id', $this->project->id)->count();

            if ($this->activeTab === 'invoices') {
                $recentInvoices = Invoice::query()
                    ->where('project_id', $this->project->id)
                    ->latest('invoice_date')
                    ->limit(10)
                    ->get();
            }
        }

        $stockOrderCount = 0;
        $recentStockOrders = collect();
        if (in_array('stock', $tabs, true)) {
            $stockQuery = StockOrder::query()->where('project_id', $this->project->id);
            if (! $user->can('viewAny', StockOrder::class)) {
                $stockQuery->where('user_id', $user->id);
            }

            $stockOrderCount = (clone $stockQuery)->count();

            if ($this->activeTab === 'stock') {
                $recentStockOrders = $stockQuery
                    ->with(['user:id,first_name,last_name'])
                    ->latest()
                    ->limit(10)
                    ->get();
            }
        }

        $documentCount = 0;
        if (in_array('documents', $tabs, true)) {
            $documentCount = Document::query()
                ->projectOwned()
                ->ownedByProject((string) $this->project->id)
                ->count();
        }

        $timeEntryCount = 0;
        $totalHours = 0.0;
        $regularHours = 0.0;
        $overtimeHours = 0.0;
        $doubleTimeHours = 0.0;
        $recentTimeEntries = collect();
        $hoursByUser = collect();
        if (in_array('time', $tabs, true)) {
            $timeQuery = TimecardEntry::query()->where('project_id', $this->project->id);

            $timeEntryCount = (clone $timeQuery)->count();
            $totalHours = (float) (clone $timeQuery)->sum('hours');
            $regularHours = (float) (clone $timeQuery)->sum('regular_hours');
            $overtimeHours = (float) (clone $timeQuery)->sum('overtime_hours');
            $doubleTimeHours = (float) (clone $timeQuery)->sum('double_time_hours');

            if ($this->activeTab === 'time') {
                $recentTimeEntries = (clone $timeQuery)
                    ->with(['user:id,first_name,last_name', 'costCode:id,code,name'])
                    ->latest('date')
                    ->limit(25)
                    ->get();

                $hoursByUser = (clone $timeQuery)
                    ->selectRaw('user_id, SUM(hours) as total_hours')
                    ->with('user:id,first_name,last_name')
                    ->groupBy('user_id')
                    ->orderByDesc('total_hours')
                    ->get();
            }
        }

        $financialSummary = null;
        if (in_array('financials', $tabs, true) && $this->activeTab === 'financials') {
            $financialSummary = app(ProjectFinancialsService::class)->summary($this->project);
        }

        return view('projects::livewire.user.projects.show', [
            'tabs' => $tabs,
            'taskCount' => $taskCount,
            'recentTasks' => $recentTasks,
            'dailyCount' => $dailyCount,
            'recentDailies' => $recentDailies,
            'invoiceCount' => $invoiceCount,
            'recentInvoices' => $recentInvoices,
            'stockOrderCount' => $stockOrderCount,
            'recentStockOrders' => $recentStockOrders,
            'documentCount' => $documentCount,
            'financialSummary' => $financialSummary,
            'timeEntryCount' => $timeEntryCount,
            'totalHours' => $totalHours,
            'regularHours' => $regularHours,
            'overtimeHours' => $overtimeHours,
            'doubleTimeHours' => $doubleTimeHours,
            'recentTimeEntries' => $recentTimeEntries,
            'hoursByUser' => $hoursByUser,
        ]);
    }
}
