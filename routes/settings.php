<?php

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Livewire\Auth\ForcePasswordChange;
use App\Core\Identity\Livewire\Settings\Appearance;
use App\Core\Identity\Livewire\Settings\Password;
use App\Core\Identity\Livewire\Settings\Profile;
use App\Core\Identity\Livewire\Settings\TwoFactor;
use App\Core\Identity\Models\User;
use App\Core\Notification\Livewire\Settings\Preferences as NotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::livewire('password/change', ForcePasswordChange::class)->name('password.change');
    Route::post('password/change', function (Request $request) {
        $validated = $request->validate([
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $user = $request->user();

        if (! $user instanceof User || ! $user->password_change_required) {
            return redirect()->route('dashboard');
        }

        $user->update([
            'password' => $validated['password'],
            'password_change_required' => false,
        ]);

        if ($user->company_email !== null) {
            try {
                app(CpanelMailboxManager::class)->syncPasswordForUser($user, $validated['password']);
            } catch (Throwable $exception) {
                Log::error('[ForcePasswordChange] Native POST cPanel sync failed.', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $request->session()->flash('status', 'Your password has been updated.');

        return redirect()->intended(route('dashboard', absolute: false));
    })->name('password.change.submit');
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/password', Password::class)->name('user-password.edit');
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::livewire('settings/notifications', NotificationPreferences::class)->name('notifications.edit');

    Route::livewire('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
