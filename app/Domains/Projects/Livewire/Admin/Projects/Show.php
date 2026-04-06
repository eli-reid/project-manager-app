<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Services\ProjectAccessService;
use App\Domains\Stock\Models\StockOrder;
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

    public string $selectedAccessUserId = '';

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

        if ($user?->can('viewAny', StockOrder::class)) {
            $tabs[] = 'stock';
        }

        if ($user?->can('viewAny', Document::class)) {
            $tabs[] = 'documents';
        }

        if ($this->canViewProjectAccessTab()) {
            $tabs[] = 'access';
        }

        return $tabs;
    }

    public function grantProjectAccess(): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->hasPermission('project-access.grant'), 403);

        $validated = $this->validate([
            'selectedAccessUserId' => ['required', 'string', 'exists:users,id'],
        ]);

        $userToGrant = User::query()->findOrFail($validated['selectedAccessUserId']);

        $projectAccessService = app(ProjectAccessService::class);

        $projectAccessService->grant(
            $this->project,
            $userToGrant,
            $actor,
            ['projects.view']
        );

        $this->selectedAccessUserId = '';
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
        $documentCount = 0;

        $accessAssignments = collect();
        $assignableUsers = collect();

        if (in_array('access', $tabs, true)) {
            $accessAssignments = ProjectUserAccess::query()
                ->with(['user:id,first_name,last_name,email', 'grantedBy:id,first_name,last_name'])
                ->where('project_id', $this->project->id)
                ->latest()
                ->get();

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

        if (in_array('documents', $tabs, true)) {
            $documentCount = Document::query()
                ->projectOwned()
                ->ownedByProject((string) $this->project->id)
                ->count();
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
            'documentCount' => $documentCount,
            'accessAssignments' => $accessAssignments,
            'assignableUsers' => $assignableUsers,
        ]);
    }
}
