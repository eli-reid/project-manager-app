<?php

namespace App\Domains\Projects\Livewire\User\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Projects')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    #[Url(as: 'visibility', except: 'assigned')]
    public string $visibilityScope = 'assigned';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVisibilityScope(): void
    {
        if (! in_array($this->visibilityScope, ['assigned', 'permitted'], true)) {
            $this->visibilityScope = 'assigned';
        }

        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $closedStatuses = [
            ProjectStatusEnum::COMPLETED->value,
            ProjectStatusEnum::FINAL_INSPECTION->value,
            ProjectStatusEnum::CANCELLED->value,
            ProjectStatusEnum::ARCHIVED->value,
        ];

        $projects = Project::query()
            ->with(['address:id,address1,address2,city,state,zip,country'])
            ->where('is_active', true)
            ->whereNotIn('status', $closedStatuses)
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', $search)
                        ->orWhere('project_number', 'like', $search)
                        ->orWhereHas('address', function (Builder $addressQuery) use ($search): void {
                            $addressQuery->where('address1', 'like', $search)
                                ->orWhere('address2', 'like', $search)
                                ->orWhere('city', 'like', $search)
                                ->orWhere('state', 'like', $search)
                                ->orWhere('zip', 'like', $search);
                        });
                });
            });

        $this->applyVisibilityScope($projects, $user);

        $projects = $projects
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->paginate(10);

        return view('projects::livewire.user.projects.index', [
            'projects' => $projects,
        ]);
    }

    private function applyVisibilityScope(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($this->visibilityScope === 'permitted') {
            $this->applyPermittedScope($query, $user);

            return;
        }

        $this->applyAssignedScope($query, $user);
    }

    private function applyAssignedScope(Builder $query, User $user): void
    {
        $activeRoleIds = $user->roles()
            ->where('is_active', true)
            ->pluck('roles.id')
            ->all();

        $query->where(function (Builder $builder) use ($user, $activeRoleIds): void {
            $builder->where('project_manager_id', $user->id)
                ->orWhereHas('userAccesses', function (Builder $accessQuery) use ($user): void {
                    $accessQuery
                        ->where('user_id', $user->id)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });

            if ($activeRoleIds !== []) {
                $builder->orWhereHas('roleAccesses', function (Builder $accessQuery) use ($activeRoleIds): void {
                    $accessQuery
                        ->whereIn('role_id', $activeRoleIds)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });
            }
        });
    }

    private function applyPermittedScope(Builder $query, User $user): void
    {
        $activeRoleIds = $user->roles()
            ->where('is_active', true)
            ->pluck('roles.id')
            ->all();

        $query->where(function (Builder $builder) use ($user, $activeRoleIds): void {
            $builder
                ->where(function (Builder $unscopedQuery): void {
                    $unscopedQuery
                        ->whereDoesntHave('userAccesses')
                        ->whereDoesntHave('roleAccesses');
                })
                ->orWhereHas('userAccesses', function (Builder $accessQuery) use ($user): void {
                    $accessQuery
                        ->where('user_id', $user->id)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });

            if ($activeRoleIds !== []) {
                $builder->orWhereHas('roleAccesses', function (Builder $accessQuery) use ($activeRoleIds): void {
                    $accessQuery
                        ->whereIn('role_id', $activeRoleIds)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });
            }
        });
    }
}
