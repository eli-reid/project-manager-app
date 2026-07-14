<?php

namespace App\Core\WeatherApi\Providers;

use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\WeatherApi\Console\Commands\SyncStoredWeatherData;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Core\WeatherApi\Services\WeatherApiService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class WeatherApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherApiContract::class, WeatherApiService::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry, DashboardWidgetRegistry $widgetRegistry): void
    {
        $settingsRegistry->registerConfigFile('weather', __DIR__.'/../config/settings.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'weather');
        $this->commands([
            SyncStoredWeatherData::class,
        ]);
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
                // Place the weather widget in the primary (general) section so it
                // appears near Company Announcements and give it the same half
                // width so it lines up underneath the announcements widget.
                section: 'primary',
                // Sort just after announcements (announcements uses 10) so the
                // weather widget renders immediately after it and will pack
                // underneath when the grid lays out.
                sort: 11,
                span: 'half',
                ability: '',
                abilityModel: '',
                title: '5 Day Forecast',
                description: 'A five day weather forecast for your default location.',
            ),
        ]);
    }
}
