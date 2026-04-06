<?php

namespace App\Core\Identity\Providers;

use App\Core\Identity\Livewire\Settings\Appearance;
use App\Core\Identity\Livewire\Settings\DeleteUserForm;
use App\Core\Identity\Livewire\Settings\Password;
use App\Core\Identity\Livewire\Settings\Profile;
use App\Core\Identity\Livewire\Settings\TwoFactor;
use App\Core\Identity\Livewire\Settings\TwoFactor\RecoveryCodes;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerUIComponents();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-user');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('settings.profile', Profile::class);
        Livewire::component('settings.password', Password::class);
        Livewire::component('settings.appearance', Appearance::class);
        Livewire::component('settings.two-factor', TwoFactor::class);
        Livewire::component('settings.delete-user-form', DeleteUserForm::class);
        Livewire::component('settings.two-factor.recovery-codes', RecoveryCodes::class);
    }
}
