<?php

namespace App\Core\WeatherApi\Tests\Feature;

use App\Core\WeatherApi\Contracts\WeatherApiContract;
use Livewire\Livewire;

it('renders five day forecast using the weather api', function () {
    $this->mock(WeatherApiContract::class, function ($mock) {
        $sample = [
            'location' => ['name' => 'Test City', 'region' => '', 'country' => 'Testland'],
            'forecast' => [
                'forecastday' => [
                    [
                        'date' => now()->toDateString(),
                        'day' => [
                            'avgtemp_f' => 70,
                            'condition' => ['text' => 'Sunny', 'icon' => ''],
                        ],
                    ],
                ],
            ],
            'current' => [],
        ];

        $mock->shouldReceive('getForecastWeather')->andReturn($sample);
        $mock->shouldReceive('extractWeatherForDailyReport')->andReturnUsing(function ($data) {
            return [
                'date' => $data['forecast']['forecastday'][0]['date'] ?? now()->toDateString(),
                'temperature' => $data['forecast']['forecastday'][0]['day']['avgtemp_f'] ?? null,
                'condition' => $data['forecast']['forecastday'][0]['day']['condition']['text'] ?? null,
                'location_name' => $data['location']['name'] ?? null,
            ];
        });
    });

    $component = Livewire::test(\App\Core\WeatherApi\Livewire\Dashboard\Widget::class);

    $forecast = $component->get('forecast');

    expect(count($forecast))->toBe(5);
    $component->assertSee('5 Day Forecast');
    $component->assertSee('Test City');
});
