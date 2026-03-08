<?php

namespace App\Core\WeatherApi\Providers;

use App\Core\WeatherApi\Services\WeatherApiService;
use Illuminate\Support\ServiceProvider;

class WeatherApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherApiService::class, function (): WeatherApiService {
            return new WeatherApiService;
        });
    }
}
