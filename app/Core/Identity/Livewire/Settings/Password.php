<?php

namespace App\Core\Identity\Livewire\Settings;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Concerns\PasswordValidationRules;
use App\Core\Identity\Models\User;
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
        $view = request()->routeIs('settings.mobile.*')
            ? view('core-user::livewire.mobile.settings.password')
            : view('core-user::livewire.settings.password');

        if (request()->routeIs('settings.mobile.*')) {
            return $view->layout('layouts.mobile', ['title' => __('Password settings')]);
        }

        return $view;
    }
}
