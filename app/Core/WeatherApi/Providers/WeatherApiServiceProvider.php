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

    public function boot(SettingsRegistryContract $settingsRegistry, \App\Core\Dashboard\Services\DashboardWidgetRegistry $widgetRegistry): void
    {
        $settingsRegistry->registerConfigFile('weather', __DIR__.'/../config/settings.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'weather');
        $this->registerUIComponents();
        $this->registerDashboardWidgets($widgetRegistry);
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('weather', classNamespace: 'App\Core\WeatherApi\Livewire');
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
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
    }
}
