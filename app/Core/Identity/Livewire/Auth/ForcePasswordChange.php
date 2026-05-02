<?php

namespace App\Core\Identity\Livewire\Auth;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Concerns\PasswordValidationRules;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'password' => 'Unable to update password because your session has expired. Please sign in again.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
            'password_change_required' => false,
        ]);

        if ($user->company_email !== null) {
            try {
                app(CpanelMailboxManager::class)->syncPasswordForUser($user, $validated['password']);
            } catch (\Throwable $exception) {
                Log::warning('Failed to sync forced password change to cPanel mailbox.', [
                    'user_id' => (string) $user->id,
                    'company_email' => $user->company_email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        session()->flash('status', 'Your password has been updated.');

        $this->redirectIntended(default: route('dashboard', absolute: false));
    }

    public function render()
    {
        return view('core-user::livewire.auth.force-password-change');
    }
}
