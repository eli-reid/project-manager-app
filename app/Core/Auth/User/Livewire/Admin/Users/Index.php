<?php

namespace App\Core\Auth\User\Livewire\Admin\Users;

use App\Core\Auth\User\Actions\Admin\ResetUserPasswordWithGeneratedPassword;
use App\Core\Identity\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('core-user::livewire.layouts.access-admin')]
#[Title('Users')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected ResetUserPasswordWithGeneratedPassword $resetUserPasswordWithGeneratedPassword;

    public string $search = '';

    public function boot(ResetUserPasswordWithGeneratedPassword $resetUserPasswordWithGeneratedPassword): void
    {
        $this->resetUserPasswordWithGeneratedPassword = $resetUserPasswordWithGeneratedPassword;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('update', $user);

        if ((string) $user->id === (string) Auth::id()) {
            session()->flash('error', 'You cannot disable your own account.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('success', 'User status updated.');
    }

    public function deleteUser(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('delete', $user);
        /**
         * Prevent users from deleting their own account to avoid accidental lockout.
         */
        if ((string) $user->id === (string) Auth::id()) {
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user->delete();

        session()->flash('success', 'User deleted successfully.');
    }

    public function resendInvite(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('update', $user);

        if ((string) $user->id === (string) Auth::id()) {
            session()->flash('error', 'You cannot resend an invite to your own account.');

            return;
        }

        $actor = Auth::user();
        $this->resetUserPasswordWithGeneratedPassword->handle($user, $actor, 'admin.users.invite.resent');

        session()->flash('success', 'Invitation resent with a new temporary password.');
    }

    public function resetPassword(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('update', $user);

        if ((string) $user->id === (string) Auth::id()) {
            session()->flash('error', 'You cannot reset your own password from this screen.');

            return;
        }

        $actor = Auth::user();
        $this->resetUserPasswordWithGeneratedPassword->handle($user, $actor, 'admin.users.password.reset');

        session()->flash('success', 'A new temporary password was generated and sent to the user.');
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

        return view('auth-user::livewire.admin.users.index', [
            'users' => $users,
        ]);
    }
}
