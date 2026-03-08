<?php

namespace App\Core\User\Livewire\Admin\Roles;

use App\Core\User\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Roles')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(string $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->authorize('update', $role);

        if (! $role->toggleStatus()) {
            session()->flash('error', 'Built-in roles cannot be disabled.');

            return;
        }

        session()->flash('success', "Role '{$role->name}' status updated.");
    }

    public function deleteRole(string $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->authorize('delete', $role);

        if (! $role->delete()) {
            session()->flash('error', 'Built-in roles cannot be deleted.');

            return;
        }

        session()->flash('success', 'Role deleted successfully.');
    }

    public function render()
    {
        $roles = Role::query()
            ->withCount('users')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('built_in')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12);

        return view('core-user::livewire.admin.roles.index', [
            'roles' => $roles,
        ])->layout('layouts.app', [
            'title' => 'Roles',
        ]);
    }
}
