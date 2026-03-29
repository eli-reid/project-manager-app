<?php

namespace App\Core\User\Livewire\Auth;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Concerns\PasswordValidationRules;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Change password')]
class ForcePasswordChange extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->password_change_required) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'password' => $this->passwordRules(),
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $user->forceFill([
            'password' => $validated['password'],
            'password_change_required' => false,
        ])->save();

        app(CpanelMailboxManager::class)->syncPasswordForUser($user, $validated['password']);

        session()->flash('status', 'Your password has been updated.');

        $this->redirectIntended(default: route('dashboard', absolute: false));
    }

    public function render()
    {
        return view('core-user::livewire.auth.force-password-change');
    }
}
