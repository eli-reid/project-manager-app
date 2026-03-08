<?php

namespace App\Livewire\Admin\Users;

use App\Core\User\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(string $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ((string) $user->id === (string) auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('success', 'User status updated.');
    }

    public function deleteUser(string $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ((string) $user->id === (string) auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user->delete();

        session()->flash('success', 'User deleted successfully.');
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(12);

        return view('livewire.admin.users.index', [
            'users' => $users,
        ])->layout('layouts.app', [
            'title' => 'Users',
        ]);
    }
}
