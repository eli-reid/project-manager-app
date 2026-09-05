<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectRoleAccess;
use App\Domains\Projects\Models\ProjectUserAccess;
use App\Domains\Projects\Services\ProjectAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AccessTab extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $selectedAccessUserId = '';

    public string $selectedAccessRoleId = '';

    /**
     * @var array<int, string>
     */
    public array $selectedAccessPermissionKeys = ['projects.view'];

    private ProjectAccessService $projectAccessService;

    public function boot(ProjectAccessService $projectAccessService): void
    {
        $this->projectAccessService = $projectAccessService;
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
        abort_unless($user instanceof User, 401);

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

        $assignableUsers = collect();
        $assignableRoles = collect();
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

        return view('projects::livewire.admin.projects.access-tab', [
            'accessAssignments' => $accessAssignments,
            'roleAccessAssignments' => $roleAccessAssignments,
            'assignableUsers' => $assignableUsers,
            'assignableRoles' => $assignableRoles,
            'availableAccessPermissionOptions' => $availableAccessPermissionOptions,
        ]);
    }
}
