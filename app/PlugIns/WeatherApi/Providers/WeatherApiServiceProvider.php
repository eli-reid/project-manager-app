<?php

namespace App\PlugIns\WeatherApi\Providers;

use App\PlugIns\WeatherApi\Contracts\WeatherApiContract;
use App\PlugIns\WeatherApi\Services\WeatherApiService;
use Illuminate\Support\ServiceProvider;

class WeatherApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherApiContract::class, WeatherApiService::class);
    }
}
