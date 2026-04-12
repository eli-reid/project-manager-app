<?php

namespace App\Domains\Payroll\Providers;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('payroll', __DIR__.'/../config/settings.php');
    }
}
