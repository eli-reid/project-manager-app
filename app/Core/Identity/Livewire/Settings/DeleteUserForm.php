<?php

namespace App\Core\Identity\Livewire\Settings;

use App\Core\Identity\Actions\User\Logout;
use App\Core\Identity\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('core-user::livewire.settings.delete-user-form');
    }
}
