<?php

use App\Core\WeatherApi\Services\WeatherApiService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.weatherapi.key', 'test-weather-key');
    config()->set('services.weatherapi.base_url', 'https://api.weatherapi.com/v1');
    config()->set('services.weatherapi.cache_duration', 60);
    config()->set('services.weatherapi.timeout', 10);

    Cache::flush();
});

it('caches current weather by location', function (): void {
    $service = app(WeatherApiService::class);

    Http::fake([
        'https://api.weatherapi.com/v1/current.json*' => Http::response([
            'location' => ['name' => 'Denver'],
            'current' => ['temp_f' => 72.5],
        ], 200),
    ]);

    $first = $service->getCurrentWeather('denver,co');
    $second = $service->getCurrentWeather('denver,co');

    expect($first)->toBeArray()
        ->and($second)->toBeArray()
        ->and($first['current']['temp_f'])->toBe(72.5)
        ->and($second['current']['temp_f'])->toBe(72.5);

    Http::assertSentCount(1);
});

it('uses historical endpoint for past dates', function (): void {
    $service = app(WeatherApiService::class);

    Http::fake([
        'https://api.weatherapi.com/v1/history.json*' => Http::response([
            'forecast' => [
                'forecastday' => [
                    ['date' => '2025-01-10', 'day' => ['avgtemp_f' => 51.0]],
                ],
            ],
        ], 200),
    ]);

    $result = $service->getForecastWeather('aurora,co', Carbon::parse('2025-01-10'));

    expect($result)->toBeArray()
        ->and((float) $result['forecast']['forecastday'][0]['day']['avgtemp_f'])->toBe(51.0);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/history.json')
            && str_contains($request->url(), 'q=aurora%2Cco')
            && str_contains($request->url(), 'dt=2025-01-10');
    });
});

it('uses ip geolocation and then forecast lookup', function (): void {
    $service = app(WeatherApiService::class);

    Http::fake([
        'https://api.weatherapi.com/v1/ip.json*' => Http::response([
            'location' => ['lat' => 39.7392, 'lon' => -104.9903],
        ], 200),
        'https://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'forecast' => [
                'forecastday' => [
                    ['date' => now()->toDateString(), 'day' => ['avgtemp_f' => 68.4]],
                ],
            ],
        ], 200),
    ]);

    $result = $service->getWeatherByIp('8.8.8.8', now());

    expect($result)->toBeArray()
        ->and($result['forecast']['forecastday'][0]['day']['avgtemp_f'])->toBe(68.4);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/ip.json') && str_contains($request->url(), 'q=8.8.8.8');
    });

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/forecast.json')
            && str_contains($request->url(), 'q=39.7392%2C-104.9903');
    });
});

it('extracts daily report weather fields from current payload', function (): void {
    $service = app(WeatherApiService::class);

    $payload = [
        'location' => [
            'name' => 'Denver',
            'region' => 'Colorado',
            'country' => 'United States',
            'localtime' => '2026-03-08 13:45',
        ],
        'current' => [
            'condition' => ['text' => 'Partly cloudy', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/116.png'],
            'temp_f' => 62.7,
            'wind_mph' => 9.4,
            'wind_dir' => 'NW',
            'precip_in' => 0.0,
            'humidity' => 31,
        ],
    ];

    $result = $service->extractWeatherForDailyReport($payload);

    expect($result['condition'])->toBe('Partly cloudy')
        ->and($result['temperature'])->toBe(62.7)
        ->and($result['wind_direction'])->toBe('NW')
        ->and($result['location_name'])->toBe('Denver, Colorado')
        ->and($result['date'])->toBe('2026-03-08');
});
