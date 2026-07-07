<?php

namespace App\Core\WeatherApi\Livewire\Dashboard;

use App\Core\WeatherApi\Contracts\WeatherApiContract;
use Carbon\Carbon;
use Livewire\Component;
use App\Core\Settings\Facades\Settings;

class Widget extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $forecast = [];

    public string $location = '';

    public function mount(WeatherApiContract $weatherApi): void
    {
        $this->location = Settings::get('weatherapi.default_location');

        if ($this->location === '') {
            $this->forecast = [];

            return;
        }

        $items = [];

        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::today()->addDays($i);

            $data = $weatherApi->getForecastWeather($this->location, $date);

            if ($data !== null) {
                $items[] = $weatherApi->extractWeatherForDailyReport($data);
            } else {
                $items[] = [
                    'date' => $date->toDateString(),
                    'temperature' => null,
                    'condition' => null,
                    'location_name' => null,
                    'full_data' => null,
                ];
            }
        }

        $this->forecast = $items;
    }

    public function render()
    {
        return view('weather::dashboard.widget');
    }
}
