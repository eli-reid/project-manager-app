<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Services\ProjectAccessService;
use App\Domains\Projects\Services\ProjectFinancialsService;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Tasks\Models\Task;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Project Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $selectedAccessUserId = '';

    public string $selectedAccessRoleId = '';

    /**
     * @var array<int, string>
     */
    public array $selectedAccessPermissionKeys = ['projects.view'];

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public int $taskWidgetVersion = 0;

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

    #[On('project-tasks-updated')]
    public function refreshTaskMetrics(string $projectId): void
    {
        if ($projectId !== (string) $this->project->id) {
            return;
        }

        $this->taskWidgetVersion++;
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

        if ($user?->can('viewAny', StockOrder::class)) {
            $tabs[] = 'stock';
        }

        if ($user?->can('viewAny', Submittal::class)) {
            $tabs[] = 'submittals';
        } elseif ($user?->can('create', Submittal::class)) {
            $tabs[] = 'submittals';
        }

        if ($user?->can('viewAny', Document::class)) {
            $tabs[] = 'documents';
        }

        if ($this->canViewProjectAccessTab()) {
            $tabs[] = 'access';
        }

        if ($user?->can('viewAny', Timecard::class)) {
            $tabs[] = 'time';
        }

        if ($user?->can('viewFinancials', $this->project)) {
            $tabs[] = 'financials';
        }

        return $tabs;
    }

    public function grantProjectAccess(): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->hasPermission('project-access.grant'), 403);

        $validated = $this->validate([
            'selectedAccessUserId' => ['required', 'string', 'exists:users,id'],
            'selectedAccessPermissionKeys' => ['required', 'array', 'min:1'],
            'selectedAccessPermissionKeys.*' => ['string', 'in:projects.view,projects.edit,projects.delete'],
        ]);

        $userToGrant = User::query()->findOrFail($validated['selectedAccessUserId']);

        $projectAccessService = app(ProjectAccessService::class);

        $projectAccessService->grant(
            $this->project,
            $userToGrant,
            $actor,
            $validated['selectedAccessPermissionKeys']
        );

        $this->selectedAccessUserId = '';
        $this->selectedAccessPermissionKeys = ['projects.view'];
    }

    public function revokeProjectAccess(string $userId): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->hasPermission('project-access.revoke'), 403);

        $projectAccessService = app(ProjectAccessService::class);

        $userToRevoke = User::query()->find($userId);
        if (! $userToRevoke instanceof User) {
            return;
        }

        $projectAccessService->revoke($this->project, $userToRevoke, $actor);
    }

    public function grantProjectRoleAccess(): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->hasPermission('project-access.grant'), 403);

        $validated = $this->validate([
            'selectedAccessRoleId' => ['required', 'string', 'exists:roles,id'],
            'selectedAccessPermissionKeys' => ['required', 'array', 'min:1'],
            'selectedAccessPermissionKeys.*' => ['string', 'in:projects.view,projects.edit,projects.delete'],
        ]);

        $roleToGrant = Role::query()->findOrFail($validated['selectedAccessRoleId']);

        app(ProjectAccessService::class)->grantRole(
            $this->project,
            $roleToGrant,
            $actor,
            $validated['selectedAccessPermissionKeys']
        );

        $this->selectedAccessRoleId = '';
        $this->selectedAccessPermissionKeys = ['projects.view'];
    }

    public function revokeProjectRoleAccess(string $roleId): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->hasPermission('project-access.revoke'), 403);

        $roleToRevoke = Role::query()->find($roleId);
        if (! $roleToRevoke instanceof Role) {
            return;
        }

        app(ProjectAccessService::class)->revokeRole($this->project, $roleToRevoke, $actor);
    }

    private function canViewProjectAccessTab(): bool
    {
        $user = Auth::user();

        return $user?->hasPermission('project-access.view')
            || $user?->hasPermission('project-access.grant')
            || $user?->hasPermission('project-access.revoke')
            || $user?->hasPermission('project-access.manage');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $tabs = $this->tabs();

        $dailyCount = 0;
        $projectDailies = collect();
        $invoiceCount = 0;
        $projectInvoices = collect();
        $stockOrderCount = 0;
        $projectStockOrders = collect();
        $submittalCount = 0;
        $projectSubmittals = collect();
        $documentCount = 0;

        $accessAssignments = collect();
        $roleAccessAssignments = collect();
        $assignableUsers = collect();
        $assignableRoles = collect();

        $availableAccessPermissionOptions = [];
        if (in_array('access', $tabs, true)) {
            $accessAssignments = ProjectUserAccess::query()
                ->with(['user:id,first_name,last_name,email', 'grantedBy:id,first_name,last_name'])
                ->where('project_id', $this->project->id)
                ->latest()
                ->get();

            $roleAccessAssignments = ProjectRoleAccess::query()
                ->with(['role:id,name,is_active', 'grantedBy:id,first_name,last_name'])
                ->where('project_id', $this->project->id)
                ->latest()
                ->get();

            $availableAccessPermissionOptions = app(ProjectAccessService::class)->availablePermissionOptions();

            if ($user->hasPermission('project-access.grant')) {
                $assignedUserIds = $accessAssignments
                    ->pluck('user_id')
                    ->filter()
                    ->values()
                    ->all();

                $assignableUsers = User::query()
                    ->where('is_active', true)
                    ->whereNotIn('id', $assignedUserIds)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get(['id', 'first_name', 'last_name', 'email']);

                $assignedRoleIds = $roleAccessAssignments
                    ->pluck('role_id')
                    ->filter()
                    ->values()
                    ->all();

                $assignableRoles = Role::query()
                    ->where('is_active', true)
                    ->whereNotIn('id', $assignedRoleIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        if (in_array('dailies', $tabs, true)) {
            $dailyCount = $this->project->dailyReports()->count();

            if ($this->activeTab === 'dailies') {
                $projectDailies = $this->project->dailyReports()
                    ->with(['user', 'submittedBy'])
                    ->latest('report_date')
                    ->limit(15)
                    ->get();
            }
        }

        if (in_array('invoices', $tabs, true)) {
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

        if (in_array('stock', $tabs, true)) {
            $stockOrderCount = StockOrder::query()
                ->where('project_id', $this->project->id)
                ->count();

            if ($this->activeTab === 'stock') {
                $projectStockOrders = StockOrder::query()
                    ->with(['user:id,first_name,last_name'])
                    ->withCount('items')
                    ->where('project_id', $this->project->id)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        if (in_array('submittals', $tabs, true)) {
            $canViewAnySubmittals = $user->can('viewAny', Submittal::class);
            $isSubmittalCreateMode = $this->activeTab === 'submittals'
                && request()->query('submittalMode') === 'create';

            if ($canViewAnySubmittals) {
                $submittalCount = Submittal::query()
                    ->where('project_id', $this->project->id)
                    ->count();
            }

            if ($canViewAnySubmittals && $this->activeTab === 'submittals' && ! $isSubmittalCreateMode) {
                $projectSubmittals = Submittal::query()
                    ->with([
                        'submittedBy:id,first_name,last_name',
                        'currentReviewer:id,first_name,last_name',
                    ])
                    ->withCount(['items', 'approvals'])
                    ->where('project_id', $this->project->id)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        if (in_array('documents', $tabs, true)) {
            $documentCount = app(ProjectDocumentLibraryContract::class)
                ->countProjectAccessible((string) $this->project->id);
        }

        $timeEntryCount = 0;
        $totalHours = 0.0;
        $regularHours = 0.0;
        $overtimeHours = 0.0;
        $doubleTimeHours = 0.0;
        $recentTimeEntries = collect();
        $hoursByUser = collect();
        if (in_array('time', $tabs, true)) {
            $metricsService = app(ProjectTimecardMetricsService::class);
            $summary = $metricsService->summaryForProject((string) $this->project->id);

            $timeEntryCount = $summary['time_entry_count'];
            $totalHours = $summary['total_hours'];
            $regularHours = $summary['regular_hours'];
            $overtimeHours = $summary['overtime_hours'];
            $doubleTimeHours = $summary['double_time_hours'];

            if ($this->activeTab === 'time') {
                $detail = $metricsService->detailForProject((string) $this->project->id);
                $recentTimeEntries = $detail['recent_time_entries'];
                $hoursByUser = $detail['hours_by_user'];
            }
        }

        $financialSummary = null;
        if (in_array('financials', $tabs, true) && $this->activeTab === 'financials') {
            $financialSummary = app(ProjectFinancialsService::class)->summary($this->project);
        }

        return view('projects::livewire.admin.projects.show', [
            'tabs' => $tabs,
            'dailyCount' => $dailyCount,
            'projectDailies' => $projectDailies,
            'taskCount' => Task::query()->where('project_id', $this->project->id)->count(),
            'invoiceCount' => $invoiceCount,
            'projectInvoices' => $projectInvoices,
            'stockOrderCount' => $stockOrderCount,
            'projectStockOrders' => $projectStockOrders,
            'submittalCount' => $submittalCount,
            'projectSubmittals' => $projectSubmittals,
            'documentCount' => $documentCount,
            'accessAssignments' => $accessAssignments,
            'roleAccessAssignments' => $roleAccessAssignments,
            'assignableUsers' => $assignableUsers,
            'assignableRoles' => $assignableRoles,
            'availableAccessPermissionOptions' => $availableAccessPermissionOptions,
            'timeEntryCount' => $timeEntryCount,
            'totalHours' => $totalHours,
            'regularHours' => $regularHours,
            'overtimeHours' => $overtimeHours,
            'doubleTimeHours' => $doubleTimeHours,
            'recentTimeEntries' => $recentTimeEntries,
            'hoursByUser' => $hoursByUser,
            'financialSummary' => $financialSummary,
            'projectAddress' => $this->project->loadMissing('address')->address,
        ]);
    }
}
