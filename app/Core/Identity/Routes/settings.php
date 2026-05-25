<?php

use App\Core\Identity\Livewire\Auth\ForcePasswordChange;
use App\Core\Identity\Livewire\Settings\Appearance;
use App\Core\Identity\Livewire\Settings\Password;
use App\Core\Identity\Livewire\Settings\Profile;
use App\Core\Identity\Livewire\Settings\TwoFactor;
use App\Core\Notification\Http\Controllers\PushSubscriptionController;
use App\Core\Notification\Livewire\Settings\Preferences as NotificationPreferences;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::livewire('password/change', ForcePasswordChange::class)->name('password.change');
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/password', Password::class)->name('user-password.edit');
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::livewire('settings/notifications', NotificationPreferences::class)->name('notifications.edit');
    Route::post('push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push/subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

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
