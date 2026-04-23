<?php

namespace App\Core\Identity\Livewire\Auth;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Concerns\PasswordValidationRules;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

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
        try {
            $validated = $this->validate([
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('password', 'password_confirmation');

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
        session()->flash('status', 'Your password has been updated.');

        $this->redirectIntended(default: route('dashboard', absolute: false));
    }

    public function render()
    {
        return view('core-user::livewire.auth.force-password-change');
    }
}
