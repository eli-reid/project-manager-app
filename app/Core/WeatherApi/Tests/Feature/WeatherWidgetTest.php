<?php

namespace App\Core\WeatherApi\Tests\Feature;

use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Core\WeatherApi\Livewire\Dashboard\Widget;
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
                            'maxtemp_f' => 75,
                            'mintemp_f' => 65,
                            'condition' => ['text' => 'Sunny', 'icon' => ''],
                        ],
                    ],
                    [
                        'date' => now()->addDay()->toDateString(),
                        'day' => [
                            'avgtemp_f' => 68,
                            'maxtemp_f' => 73,
                            'mintemp_f' => 63,
                            'condition' => ['text' => 'Cloudy', 'icon' => ''],
                        ],
                    ],
                    [
                        'date' => now()->addDays(2)->toDateString(),
                        'day' => [
                            'avgtemp_f' => 66,
                            'maxtemp_f' => 71,
                            'mintemp_f' => 61,
                            'condition' => ['text' => 'Rain', 'icon' => ''],
                        ],
                    ],
                    [
                        'date' => now()->addDays(3)->toDateString(),
                        'day' => [
                            'avgtemp_f' => 64,
                            'maxtemp_f' => 69,
                            'mintemp_f' => 59,
                            'condition' => ['text' => 'Mist', 'icon' => ''],
                        ],
                    ],
                    [
                        'date' => now()->addDays(4)->toDateString(),
                        'day' => [
                            'avgtemp_f' => 62,
                            'maxtemp_f' => 67,
                            'mintemp_f' => 57,
                            'condition' => ['text' => 'Partly cloudy', 'icon' => ''],
                        ],
                    ],
                ],
            ],
            'current' => [],
        ];

        $mock->shouldReceive('getForecastWeather')->once()->andReturn($sample);
    });

    $component = Livewire::test(Widget::class);

    $forecast = $component->get('forecast');

    expect(count($forecast))->toBe(5);
    $component->assertSee('5 Day Forecast');
    $component->assertSee('Test City');
    $component->assertSee('H 75°');
    $component->assertSee('L 65°');
});
