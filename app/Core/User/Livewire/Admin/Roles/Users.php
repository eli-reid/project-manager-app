<?php

namespace App\Core\User\Livewire\Admin\Roles;

use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Role Users')]
class Users extends Component
{
    public Role $role;

    public string $searchAssigned = '';

    public string $searchAvailable = '';

    /**
     * @var array<int, string>
     */
    public array $selectedUserIds = [];

    public function mount(Role $role): void
    {
        $this->role = $role;
    }

    public function assignSelectedUsers(): void
    {
        $validated = $this->validate([
            'selectedUserIds' => ['required', 'array', 'min:1'],
            'selectedUserIds.*' => ['exists:users,id'],
        ]);

        $this->role->users()->syncWithoutDetaching($validated['selectedUserIds']);
        $this->selectedUserIds = [];

        session()->flash('success', 'Users assigned successfully.');
    }

    public function removeUser(string $userId): void
    {
        $this->role->users()->detach($userId);
        session()->flash('success', 'User removed from role successfully.');
    }

    public function render()
    {
        $assignedUsers = $this->role->users()
            ->when($this->searchAssigned !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('first_name', 'like', '%'.$this->searchAssigned.'%')
                        ->orWhere('last_name', 'like', '%'.$this->searchAssigned.'%')
                        ->orWhere('username', 'like', '%'.$this->searchAssigned.'%')
                        ->orWhere('email', 'like', '%'.$this->searchAssigned.'%');
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $availableUsers = User::query()
            ->whereDoesntHave('roles', function ($query): void {
                $query->where('roles.id', $this->role->id);
            })
            ->when($this->searchAvailable !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('first_name', 'like', '%'.$this->searchAvailable.'%')
                        ->orWhere('last_name', 'like', '%'.$this->searchAvailable.'%')
                        ->orWhere('username', 'like', '%'.$this->searchAvailable.'%')
                        ->orWhere('email', 'like', '%'.$this->searchAvailable.'%');
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(50)
            ->get();

        return view('core-user::livewire.admin.roles.users', [
            'assignedUsers' => $assignedUsers,
            'availableUsers' => $availableUsers,
        ])->layout('layouts.app', [
            'title' => 'Role Users',
        ]);
    }
}
