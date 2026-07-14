<?php

use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Services\WeatherApiService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    DB::table('weather_records')->insert([
        'location_key' => 'denver,co',
        'source_location' => 'denver,co',
        'location_name' => 'Denver, CO',
        'record_type' => 'history',
        'weather_date' => Carbon::yesterday()->toDateString(),
        'temperature' => 65.0,
        'temperature_high' => 70.0,
        'temperature_low' => 60.0,
        'temperature_unit' => 'F',
        'wind_speed' => 5.0,
        'wind_direction' => null,
        'precipitation' => 0.0,
        'humidity' => 40,
        'condition_text' => 'Sunny',
        'weather_icon' => null,
        'synced_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

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
        ->and($result['temperature_high'])->toBeNull()
        ->and($result['temperature_low'])->toBeNull()
        ->and($result['wind_direction'])->toBe('NW')
        ->and($result['location_name'])->toBe('Denver, Colorado')
        ->and($result['date'])->toBe('2026-03-08');
});

it('returns forecast payload from stored weather records without calling the api', function (): void {
    $service = app(WeatherApiService::class);
    $today = Carbon::today();

    $records = [[
        'location_key' => '02766',
        'source_location' => '02766',
        'location_name' => 'Norton, MA',
        'record_type' => 'current',
        'weather_date' => $today->toDateString(),
        'temperature' => 74.5,
        'temperature_high' => null,
        'temperature_low' => null,
        'temperature_unit' => 'F',
        'wind_speed' => 7.5,
        'wind_direction' => 'NW',
        'precipitation' => 0.0,
        'humidity' => 40,
        'condition_text' => 'Sunny',
        'weather_icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png',
        'synced_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]];

    foreach (range(0, 2) as $offset) {
        $records[] = [
            'location_key' => '02766',
            'source_location' => '02766',
            'location_name' => 'Norton, MA',
            'record_type' => 'forecast',
            'weather_date' => $today->copy()->addDays($offset)->toDateString(),
            'temperature' => 72.0 - $offset,
            'temperature_high' => 78.0 - $offset,
            'temperature_low' => 65.0 - $offset,
            'temperature_unit' => 'F',
            'wind_speed' => 10.0 - $offset,
            'wind_direction' => null,
            'precipitation' => 0.1,
            'humidity' => 48,
            'condition_text' => $offset === 1 ? 'Cloudy' : 'Sunny',
            'weather_icon' => $offset === 1 ? '//cdn.weatherapi.com/weather/64x64/day/119.png' : '//cdn.weatherapi.com/weather/64x64/day/113.png',
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    DB::table('weather_records')->insert($records);

    Http::fake();

    $result = $service->getForecastWeather('02766', $today);

    expect($result)->toBeArray()
        ->and($result['location']['name'])->toBe('Norton, MA')
        ->and($result['current']['temp_f'])->toBe(74.5)
        ->and($result['forecast']['forecastday'][0]['day']['maxtemp_f'])->toBe(78.0)
        ->and($result['forecast']['forecastday'][1]['day']['condition']['text'])->toBe('Cloudy');

    Http::assertNothingSent();
});

it('refreshes forecast data when stored forecast rows are incomplete', function (): void {
    $service = app(WeatherApiService::class);
    $today = Carbon::today();

    DB::table('weather_records')->insert([
        [
            'location_key' => '02766',
            'source_location' => '02766',
            'location_name' => 'Norton, MA',
            'record_type' => 'forecast',
            'weather_date' => $today->toDateString(),
            'temperature' => 72.0,
            'temperature_high' => 78.0,
            'temperature_low' => 65.0,
            'temperature_unit' => 'F',
            'wind_speed' => 10.0,
            'wind_direction' => null,
            'precipitation' => 0.1,
            'humidity' => 48,
            'condition_text' => 'Sunny',
            'weather_icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png',
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'location_key' => '02766',
            'source_location' => '02766',
            'location_name' => 'Norton, MA',
            'record_type' => 'forecast',
            'weather_date' => $today->copy()->addDay()->toDateString(),
            'temperature' => 70.0,
            'temperature_high' => 76.0,
            'temperature_low' => 63.0,
            'temperature_unit' => 'F',
            'wind_speed' => 8.0,
            'wind_direction' => null,
            'precipitation' => 0.0,
            'humidity' => 45,
            'condition_text' => 'Cloudy',
            'weather_icon' => '//cdn.weatherapi.com/weather/64x64/day/119.png',
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'location_key' => '02766',
            'source_location' => '02766',
            'location_name' => 'Norton, MA',
            'record_type' => 'forecast',
            'weather_date' => $today->copy()->addDays(2)->toDateString(),
            'temperature' => 68.0,
            'temperature_high' => 74.0,
            'temperature_low' => 61.0,
            'temperature_unit' => 'F',
            'wind_speed' => 7.0,
            'wind_direction' => null,
            'precipitation' => 0.0,
            'humidity' => 43,
            'condition_text' => 'Rain',
            'weather_icon' => '//cdn.weatherapi.com/weather/64x64/day/296.png',
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Http::fake([
        'https://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'location' => [
                'name' => 'Norton',
                'region' => 'Massachusetts',
                'country' => 'United States',
                'localtime' => now()->format('Y-m-d H:i'),
            ],
            'current' => [
                'temp_f' => 74.5,
                'wind_mph' => 7.5,
                'wind_dir' => 'NW',
                'precip_in' => 0.0,
                'humidity' => 40,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
            ],
            'forecast' => [
                'forecastday' => collect(range(0, 2))->map(fn (int $offset): array => [
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'day' => [
                        'avgtemp_f' => 72 - $offset,
                        'maxtemp_f' => 78 - $offset,
                        'mintemp_f' => 65 - $offset,
                        'maxwind_mph' => 10 - $offset,
                        'totalprecip_in' => 0.1,
                        'avghumidity' => 45,
                        'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    ],
                ])->all(),
            ],
        ], 200),
    ]);

    $result = $service->getForecastWeather('02766', $today);

    expect($result)->toBeArray()
        ->and(count($result['forecast']['forecastday']))->toBe(3)
        ->and(DB::table('weather_records')->where('location_key', '02766')->where('record_type', 'forecast')->count())->toBe(3);

    Http::assertSentCount(1);
});

it('refreshes forecast data when a cached forecast payload is incomplete', function (): void {
    $service = app(WeatherApiService::class);
    $today = Carbon::today();

    Cache::put(
        sprintf('weather_forecast_%s_%s', '02766', $today->toDateString()),
        [
            'location' => [
                'name' => 'Norton',
                'region' => 'Massachusetts',
                'country' => 'United States',
            ],
            'forecast' => [
                'forecastday' => collect(range(0, 2))->map(fn (int $offset): array => [
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'day' => [
                        'avgtemp_f' => 72 - $offset,
                        'maxtemp_f' => 78 - $offset,
                        'mintemp_f' => 65 - $offset,
                        'maxwind_mph' => 10 - $offset,
                        'totalprecip_in' => 0.1,
                        'avghumidity' => 45,
                        'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    ],
                ])->all(),
            ],
            'requested_date' => $today->toDateString(),
        ],
        now()->addHour(),
    );

    Http::fake([
        'https://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'location' => [
                'name' => 'Norton',
                'region' => 'Massachusetts',
                'country' => 'United States',
                'localtime' => now()->format('Y-m-d H:i'),
            ],
            'current' => [
                'temp_f' => 74.5,
                'wind_mph' => 7.5,
                'wind_dir' => 'NW',
                'precip_in' => 0.0,
                'humidity' => 40,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
            ],
            'forecast' => [
                'forecastday' => collect(range(0, 2))->map(fn (int $offset): array => [
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'day' => [
                        'avgtemp_f' => 72 - $offset,
                        'maxtemp_f' => 78 - $offset,
                        'mintemp_f' => 65 - $offset,
                        'maxwind_mph' => 10 - $offset,
                        'totalprecip_in' => 0.1,
                        'avghumidity' => 45,
                        'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    ],
                ])->all(),
            ],
        ], 200),
    ]);

    $result = $service->getForecastWeather('02766', $today);

    expect($result)->toBeArray()
        ->and(count($result['forecast']['forecastday']))->toBe(3)
        ->and(DB::table('weather_records')->where('location_key', '02766')->where('record_type', 'forecast')->count())->toBe(3);

    Http::assertSentCount(1);
});

it('syncs default location weather into storage and prunes expired rows', function (): void {
    $service = app(WeatherApiService::class);

    Settings::set('weatherapi.default_location', '02766');
    Settings::set('weatherapi.retention_days', 2);

    DB::table('weather_records')->insert([
        'location_key' => '02766',
        'source_location' => '02766',
        'location_name' => 'Old Norton, MA',
        'record_type' => 'history',
        'weather_date' => Carbon::today()->subDays(5)->toDateString(),
        'temperature' => 60.0,
        'temperature_high' => 64.0,
        'temperature_low' => 56.0,
        'temperature_unit' => 'F',
        'wind_speed' => 5.0,
        'wind_direction' => null,
        'precipitation' => 0.0,
        'humidity' => 50,
        'condition_text' => 'Old data',
        'weather_icon' => null,
        'synced_at' => now()->subDays(5),
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    Http::fake([
        'https://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'location' => [
                'name' => 'Norton',
                'region' => 'Massachusetts',
                'country' => 'United States',
                'localtime' => now()->format('Y-m-d H:i'),
            ],
            'current' => [
                'temp_f' => 74.5,
                'wind_mph' => 7.5,
                'wind_dir' => 'NW',
                'precip_in' => 0.0,
                'humidity' => 40,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
            ],
            'forecast' => [
                'forecastday' => collect(range(0, 4))->map(fn (int $offset): array => [
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'day' => [
                        'avgtemp_f' => 72 - $offset,
                        'maxtemp_f' => 78 - $offset,
                        'mintemp_f' => 65 - $offset,
                        'maxwind_mph' => 10 - $offset,
                        'totalprecip_in' => 0.1,
                        'avghumidity' => 45,
                        'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    ],
                ])->all(),
            ],
        ], 200),
        'https://api.weatherapi.com/v1/history.json*' => function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $date = $query['dt'] ?? Carbon::yesterday()->toDateString();

            return Http::response([
                'location' => [
                    'name' => 'Norton',
                    'region' => 'Massachusetts',
                    'country' => 'United States',
                ],
                'forecast' => [
                    'forecastday' => [[
                        'date' => $date,
                        'day' => [
                            'avgtemp_f' => 66,
                            'maxtemp_f' => 70,
                            'mintemp_f' => 61,
                            'maxwind_mph' => 8,
                            'totalprecip_in' => 0.0,
                            'avghumidity' => 47,
                            'condition' => ['text' => 'Clear', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                        ],
                    ]],
                ],
            ], 200);
        },
    ]);

    $result = $service->syncStoredWeather();

    expect($result['location'])->toBe('02766')
        ->and($result['synced_records'])->toBe(8)
        ->and($result['pruned_records'])->toBe(1)
        ->and(DB::table('weather_records')->where('location_key', '02766')->where('record_type', 'forecast')->count())->toBe(5)
        ->and(DB::table('weather_records')->where('location_key', '02766')->where('record_type', 'current')->count())->toBe(1)
        ->and(DB::table('weather_records')->where('location_key', '02766')->where('record_type', 'history')->count())->toBe(2)
        ->and(DB::table('weather_records')->whereDate('weather_date', '<', Carbon::today()->subDays(2)->toDateString())->count())->toBe(0);
});

it('bootstraps a location with historical weather when current weather is requested and no records exist', function (): void {
    $service = app(WeatherApiService::class);

    Settings::set('weatherapi.retention_days', 3);

    Http::fake([
        'https://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'location' => [
                'name' => 'Taunton',
                'region' => 'Massachusetts',
                'country' => 'United States',
                'localtime' => now()->format('Y-m-d H:i'),
            ],
            'current' => [
                'temp_f' => 71.5,
                'wind_mph' => 6.0,
                'wind_dir' => 'SW',
                'precip_in' => 0.0,
                'humidity' => 42,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
            ],
            'forecast' => [
                'forecastday' => collect(range(0, 4))->map(fn (int $offset): array => [
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'day' => [
                        'avgtemp_f' => 71 - $offset,
                        'maxtemp_f' => 76 - $offset,
                        'mintemp_f' => 64 - $offset,
                        'maxwind_mph' => 9 - $offset,
                        'totalprecip_in' => 0.0,
                        'avghumidity' => 44,
                        'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    ],
                ])->all(),
            ],
        ], 200),
        'https://api.weatherapi.com/v1/history.json*' => function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $date = $query['dt'] ?? Carbon::yesterday()->toDateString();

            return Http::response([
                'location' => [
                    'name' => 'Taunton',
                    'region' => 'Massachusetts',
                    'country' => 'United States',
                ],
                'forecast' => [
                    'forecastday' => [[
                        'date' => $date,
                        'day' => [
                            'avgtemp_f' => 65,
                            'maxtemp_f' => 69,
                            'mintemp_f' => 60,
                            'maxwind_mph' => 7,
                            'totalprecip_in' => 0.0,
                            'avghumidity' => 48,
                            'condition' => ['text' => 'Cloudy', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/119.png'],
                        ],
                    ]],
                ],
            ], 200);
        },
    ]);

    $result = $service->getCurrentWeather('02780');

    expect($result)->toBeArray()
        ->and($result['current']['temp_f'])->toBe(71.5)
        ->and(DB::table('weather_records')->where('location_key', '02780')->where('record_type', 'current')->count())->toBe(1)
        ->and(DB::table('weather_records')->where('location_key', '02780')->where('record_type', 'forecast')->count())->toBe(5)
        ->and(DB::table('weather_records')->where('location_key', '02780')->where('record_type', 'history')->count())->toBe(3);

    Http::assertSentCount(4);
});
