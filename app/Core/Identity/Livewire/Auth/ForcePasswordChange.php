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
        Log::warning('[ForcePasswordChange] Entered updatePassword.', [
            'user_id' => Auth::id(),
            'has_password_value' => $this->password !== '',
            'has_password_confirmation_value' => $this->password_confirmation !== '',
        ]);

        try {
            Log::warning('[ForcePasswordChange] Starting validation.', [
                'user_id' => Auth::id(),
            ]);

            $validated = $this->validate([
                'password' => $this->passwordRules(),
            ]);

            Log::warning('[ForcePasswordChange] Validation passed.', [
                'user_id' => Auth::id(),
            ]);

            /** @var User|null $user */
            $user = Auth::user();

            if (! $user instanceof User) {
                Log::error('[ForcePasswordChange] Auth user missing before update.', [
                    'user_id' => Auth::id(),
                ]);

                throw ValidationException::withMessages([
                    'password' => 'Unable to update password because your session has expired. Please sign in again.',
                ]);
            }

            Log::warning('[ForcePasswordChange] Updating user password and required-change flag.', [
                'user_id' => $user->id,
                'password_change_required_before' => $user->password_change_required,
            ]);

            $user->update([
                'password' => $validated['password'],
                'password_change_required' => false,
            ]);

            Log::warning('[ForcePasswordChange] User password update complete.', [
                'user_id' => $user->id,
                'password_change_required_after' => $user->fresh()?->password_change_required,
            ]);

            if ($user->company_email !== null) {
                try {
                    Log::warning('[ForcePasswordChange] Starting cPanel sync.', [
                        'user_id' => $user->id,
                        'has_company_email' => true,
                    ]);

                    app(CpanelMailboxManager::class)->syncPasswordForUser($user, $validated['password']);

                    Log::warning('[ForcePasswordChange] cPanel sync complete.', [
                        'user_id' => $user->id,
                    ]);
                } catch (\Throwable $exception) {
                    Log::error('[ForcePasswordChange] cPanel sync failed.', [
                        'user_id' => $user->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            session()->flash('status', 'Your password has been updated.');

            Log::warning('[ForcePasswordChange] Redirecting user after successful update.', [
                'user_id' => $user->id,
            ]);

            $this->redirectIntended(default: route('dashboard', absolute: false));
        } catch (ValidationException $exception) {
            Log::warning('[ForcePasswordChange] Validation exception encountered.', [
                'user_id' => Auth::id(),
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('[ForcePasswordChange] Unexpected exception encountered.', [
                'user_id' => Auth::id(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function render()
    {
        return view('core-user::livewire.auth.force-password-change');
    }
}
