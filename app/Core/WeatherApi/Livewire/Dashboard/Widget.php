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
        $this->location = (string) Settings::get('weatherapi.default_location')->toNullableString() ?? '';

        if ($this->location === null || $this->location === '') {
            $this->forecast = [];

            return;
        }

        $items = [];

        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::today()->addDays($i);

            $data = $weatherApi->getForecastWeather($this->location, $date);

            if ($data !== null) {
                $dayData = $weatherApi->extractWeatherForDailyReport($data);
                $dayData['flux_icon'] = $this->mapConditionToIcon($dayData['condition'] ?? null);

                $items[] = $dayData;
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
    
    protected function mapConditionToIcon(?string $condition): string
    {
        if ($condition === null) {
            return 'sun';
        }

        $c = strtolower($condition);

        return match (true) {
            str_contains($c, 'sun') || str_contains($c, 'clear') => 'sun',
            str_contains($c, 'partly') && str_contains($c, 'cloud') => 'cloud-sun',
            str_contains($c, 'cloud') => 'cloud',
            str_contains($c, 'rain') || str_contains($c, 'shower') || str_contains($c, 'drizzle') => 'cloud-rain',
            str_contains($c, 'snow') => 'cloud-snow',
            str_contains($c, 'thunder') || str_contains($c, 'storm') => 'bolt',
            str_contains($c, 'fog') || str_contains($c, 'mist') || str_contains($c, 'haze') => 'cloud-fog',
            default => 'sun',
        };
    }

}
