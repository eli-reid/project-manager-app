<?php

use App\Core\Identity\Livewire\Settings\Appearance;
use App\Core\Identity\Livewire\Settings\Password;
use App\Core\Identity\Livewire\Settings\Profile;
use App\Core\Identity\Livewire\Settings\TwoFactor;
use App\Core\Notification\Livewire\Settings\Preferences as NotificationPreferences;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::prefix('mobile/settings')
    ->name('settings.mobile.')
    ->group(function (): void {
        Route::middleware(['auth'])->group(function (): void {
            Route::livewire('/profile', Profile::class)
                ->name('profile');
        });

        Route::middleware(['auth', 'verified'])->group(function (): void {
            Route::livewire('/password', Password::class)
                ->name('password');

            Route::livewire('/appearance', Appearance::class)
                ->name('appearance');

            Route::livewire('/notifications', NotificationPreferences::class)
                ->name('notifications');

            Route::livewire('/two-factor', TwoFactor::class)
                ->middleware(
                    when(
                        Features::canManageTwoFactorAuthentication()
                        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                        ['password.confirm'],
                        [],
                    ),
                )
                ->name('two-factor');
        });
    });
