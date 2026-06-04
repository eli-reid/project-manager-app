<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Services\ProjectAccessService;
use App\Domains\Projects\Services\ProjectFinancialsService;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\RFIs\Models\RFI;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Tasks\Models\Task;
use App\Domains\Timecards\Services\ProjectTimecardMetricsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    private ProjectAccessService $projectAccessService;

    private ProjectDocumentLibraryContract $projectDocumentLibrary;

    private ProjectTimecardMetricsService $projectTimecardMetricsService;

    private ProjectFinancialsService $projectFinancialsService;

    private ProjectTabRegistry $projectTabRegistry;

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

    public function boot(
        ProjectAccessService $projectAccessService,
        ProjectDocumentLibraryContract $projectDocumentLibrary,
        ProjectTimecardMetricsService $projectTimecardMetricsService,
        ProjectFinancialsService $projectFinancialsService,
        ProjectTabRegistry $projectTabRegistry,
    ): void {
        $this->projectAccessService = $projectAccessService;
        $this->projectDocumentLibrary = $projectDocumentLibrary;
        $this->projectTimecardMetricsService = $projectTimecardMetricsService;
        $this->projectFinancialsService = $projectFinancialsService;
        $this->projectTabRegistry = $projectTabRegistry;
    }

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

    public function sortProjectTab(string $tabKey, int $position): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $visibleTabKeys = collect($this->projectTabRegistry->visibleTabItems($this->project, $user))
            ->pluck('key')
            ->values()
            ->all();

        if (! in_array($tabKey, $visibleTabKeys, true)) {
            return;
        }

        $currentIndex = array_search($tabKey, $visibleTabKeys, true);
        if (! is_int($currentIndex)) {
            return;
        }

        unset($visibleTabKeys[$currentIndex]);
        $visibleTabKeys = array_values($visibleTabKeys);

        $position = max(0, min($position, count($visibleTabKeys)));
        array_splice($visibleTabKeys, $position, 0, [$tabKey]);

        $this->projectTabRegistry->updateUserTabOrder($user, $this->project, $visibleTabKeys);
    }

    public function hideTab(string $tabKey): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $this->projectTabRegistry->setUserTabHidden($user, $this->project, $tabKey, true);

        if ($this->activeTab === $tabKey) {
            $this->activeTab = $this->tabs()[0] ?? 'overview';
        }
    }

    public function showTab(string $tabKey): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $this->projectTabRegistry->setUserTabHidden($user, $this->project, $tabKey, false);
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
        $user = Auth::user();

        return $this->projectTabRegistry->visibleTabs($this->project, $user instanceof User ? $user : null);
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

        $this->projectAccessService->grant(
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

        $userToRevoke = User::query()->find($userId);
        if (! $userToRevoke instanceof User) {
            return;
        }

        $this->projectAccessService->revoke($this->project, $userToRevoke, $actor);
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

        $this->projectAccessService->grantRole(
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

        $this->projectAccessService->revokeRole($this->project, $roleToRevoke, $actor);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $tabs = $this->tabs();
        if (! in_array($this->activeTab, $tabs, true)) {
            $this->activeTab = $tabs[0] ?? 'overview';
        }

        $dailyCount = 0;
        $invoiceCount = 0;
        $stockOrderCount = 0;
        $submittalCount = 0;
        $changeOrderCount = 0;
        $rfiCount = 0;
        $isRfiCreateMode = false;
        $selectedDailyId = '';
        $selectedSubmittalId = '';
        $selectedChangeOrderId = '';
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

            $availableAccessPermissionOptions = $this->projectAccessService->availablePermissionOptions();

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

            $selectedDailyId = (string) request()->query('dailyId', '');
        }

        if (in_array('invoices', $tabs, true)) {
            $invoiceCount = Invoice::query()
                ->where('project_id', $this->project->id)
                ->count();
        }

        if (in_array('stock', $tabs, true)) {
            $stockOrderCount = StockOrder::query()
                ->where('project_id', $this->project->id)
                ->count();
        }

        if (in_array('submittals', $tabs, true)) {
            $canViewAnySubmittals = $user->can('viewAny', Submittal::class);
            $isSubmittalCreateMode = $this->activeTab === 'submittals'
                && $this->projectTabRegistry->isCreateMode('submittals', request());

            if ($canViewAnySubmittals) {
                $submittalCount = Submittal::query()
                    ->where('project_id', $this->project->id)
                    ->count();
            }

            $selectedSubmittalId = (string) request()->query('submittalId', '');
        }

        if (in_array('change-orders', $tabs, true)) {
            $changeOrderCount = ChangeOrder::query()
                ->where('project_id', $this->project->id)
                ->count();

            $selectedChangeOrderId = (string) request()->query('changeOrderId', '');
        }

        if (in_array('rfis', $tabs, true)) {
            $isRfiCreateMode = $this->activeTab === 'rfis'
                && $this->projectTabRegistry->isCreateMode('rfis', request());

            $rfiCount = RFI::query()
                ->where('project_id', $this->project->id)
                ->count();

        }

        if (in_array('documents', $tabs, true)) {
            $documentCount = $this->projectDocumentLibrary
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
            $summary = $this->projectTimecardMetricsService->summaryForProject((string) $this->project->id);

            $timeEntryCount = $summary['time_entry_count'];
            $totalHours = $summary['total_hours'];
            $regularHours = $summary['regular_hours'];
            $overtimeHours = $summary['overtime_hours'];
            $doubleTimeHours = $summary['double_time_hours'];

            if ($this->activeTab === 'time') {
                $detail = $this->projectTimecardMetricsService->detailForProject((string) $this->project->id);
                $recentTimeEntries = $detail['recent_time_entries'];
                $hoursByUser = $detail['hours_by_user'];
            }
        }

        $financialSummary = null;
        if (in_array('financials', $tabs, true) && $this->activeTab === 'financials') {
            $financialSummary = $this->projectFinancialsService->summary($this->project);
        }

        $visibleTabItems = $this->projectTabRegistry->visibleTabItems($this->project, $user);
        $hiddenTabItems = $this->projectTabRegistry->hiddenTabItems($this->project, $user);

        $tabBadges = [
            'dailies' => $dailyCount,
            'invoices' => $invoiceCount,
            'stock' => $stockOrderCount,
            'submittals' => $submittalCount,
            'change-orders' => $changeOrderCount,
            'rfis' => $rfiCount,
            'documents' => $documentCount,
            'access' => $accessAssignments->count() + $roleAccessAssignments->count(),
            'time' => $timeEntryCount,
        ];

        return view('projects::livewire.admin.projects.show', [
            'tabs' => $tabs,
            'visibleTabItems' => $visibleTabItems,
            'hiddenTabItems' => $hiddenTabItems,
            'tabBadges' => $tabBadges,
            'dailyCount' => $dailyCount,
            'selectedDailyId' => $selectedDailyId,
            'taskCount' => Task::query()->where('project_id', $this->project->id)->count(),
            'invoiceCount' => $invoiceCount,
            'stockOrderCount' => $stockOrderCount,
            'submittalCount' => $submittalCount,
            'selectedSubmittalId' => $selectedSubmittalId,
            'changeOrderCount' => $changeOrderCount,
            'selectedChangeOrderId' => $selectedChangeOrderId,
            'rfiCount' => $rfiCount,
            'isRfiCreateMode' => $isRfiCreateMode,
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
        ])->title('Project Details');
    }
}
