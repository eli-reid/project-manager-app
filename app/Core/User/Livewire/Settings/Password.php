<?php

namespace App\Core\User\Livewire\Settings;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Concerns\PasswordValidationRules;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Password settings')]
class Password extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
            'password_change_required' => false,
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        if ($user !== null) {
            app(CpanelMailboxManager::class)->syncPasswordForUser($user, $validated['password']);
        }

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    public function render()
    {
        return view('core-user::livewire.settings.password');
    }
}
