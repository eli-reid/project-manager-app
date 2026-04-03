<?php

use App\Core\Notification\Livewire\Settings\Preferences as NotificationPreferences;
use App\Core\User\Livewire\Auth\ForcePasswordChange;
use App\Core\User\Livewire\Settings\Appearance;
use App\Core\User\Livewire\Settings\Password;
use App\Core\User\Livewire\Settings\Profile;
use App\Core\User\Livewire\Settings\TwoFactor;
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
