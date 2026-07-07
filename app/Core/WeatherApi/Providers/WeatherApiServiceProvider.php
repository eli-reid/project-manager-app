<?php

namespace App\Core\WeatherApi\Providers;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Core\WeatherApi\Services\WeatherApiService;
use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class WeatherApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherApiContract::class, WeatherApiService::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('weather', __DIR__.'/../config/settings.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'weather');
        $this->registerUIComponents();
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('weather', classNamespace: 'App\Core\WeatherApi\Livewire');

        try {
            $widgetRegistry = $this->app->make(DashboardWidgetRegistry::class);

            $widgetRegistry->registerDefinitions([
                new WidgetDefinition(
                    key: 'weather.forecast',
                    component: 'weather::dashboard.widget',
                    section: 'personal',
                    sort: 10,
                    span: 'third',
                    ability: '',
                    abilityModel: '',
                    title: '5 Day Forecast',
                    description: 'A five day weather forecast for your default location.',
                ),
            ]);
        } catch (\Throwable $e) {
            // If the dashboard registry is not available during some bootstrap phases, ignore.
        }
    }
}
