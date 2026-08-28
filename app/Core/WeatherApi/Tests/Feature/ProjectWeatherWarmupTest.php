<?php

use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Listeners\WarmProjectWeatherLocation;
use App\Domains\Addresses\Models\Address;
use App\Domains\Projects\Events\ProjectAddressChanged;
use App\Domains\Projects\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('dispatches a project address changed event when a project is created with an address', function (): void {
    Event::fake([ProjectAddressChanged::class]);

    $address = Address::factory()->create();

    Project::factory()->create([
        'client_id' => $address->client_id,
        'address_id' => $address->id,
    ]);

    Event::assertDispatched(ProjectAddressChanged::class);
});

it('warms weather storage for a project address when the listener handles the event', function (): void {
    Settings::set('weatherapi.retention_days', 2);

    $address = Address::factory()->create([
        'address1' => '123 Main St',
        'city' => 'Norton',
        'state' => 'MA',
        'zip' => '02766',
    ]);

    $project = Project::factory()->create([
        'client_id' => $address->client_id,
        'address_id' => $address->id,
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

    app(WarmProjectWeatherLocation::class)->handle(new ProjectAddressChanged($project));

    $location = '123 Main St, Norton, MA, 02766';

    expect(DB::table('weather_records')->where('source_location', $location)->where('record_type', 'current')->count())->toBe(1)
        ->and(DB::table('weather_records')->where('source_location', $location)->where('record_type', 'forecast')->count())->toBe(5)
        ->and(DB::table('weather_records')->where('source_location', $location)->where('record_type', 'history')->count())->toBe(2);
});
