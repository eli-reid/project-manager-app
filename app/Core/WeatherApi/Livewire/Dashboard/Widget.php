<?php

namespace App\Core\WeatherApi\Livewire\Dashboard;

use App\Core\WeatherApi\Contracts\WeatherApiContract;
use Carbon\Carbon;
use Livewire\Component;
use App\Core\Settings\Facades\Settings;
use Illuminate\Support\Facades\View;
use Illuminate\View\ComponentAttributeBag;

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
                $dayData['date'] = $date->toDateString();
                $dayData['flux_icon'] = $this->mapConditionToIcon($dayData['condition'] ?? null);
            } else {
                $dayData = [
                    'date' => $date->toDateString(),
                    'temperature' => null,
                    'condition' => null,
                    'location_name' => null,
                    'full_data' => null,
                    'flux_icon' => null,
                ];
            }

            // Prepare display-friendly fields to keep Blade minimal
                use App\Support\Diagnostics\MemoryProbe;
            $displayDate = $this->formatDisplayDate($dayData['date'] ?? null);
            $iconHtml = $this->renderIconHtml($dayData['flux_icon'] ?? null);

            $items[] = array_merge($dayData, [
                'display_date' => $displayDate,
                'icon_html' => $iconHtml,
            ]);
        }

        $this->forecast = $items;
    }

    protected function formatDisplayDate(?string $date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            return Carbon::parse($date)->format('M j');
        } catch (\Throwable) {
            return $date;
        }
    }

    protected function renderIconHtml(?string $iconName): string
    {
        if (empty($iconName)) {
            return '';
        }

        if (View::exists('flux.icons.' . $iconName)) {
            return view('flux.icons.' . $iconName, ['attributes' => new ComponentAttributeBag([])])->render();
        }

        $base = explode('-', $iconName)[0] ?? $iconName;
        $emoji = match ($base) {
            'cloud', 'rain', 'snow' => '☁️',
            'bolt', 'lightning' => '⚡️',
            'sun', 'clear' => '☀️',
            default => '🌤️',
        };
            $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.weather.forecast.mount.start') : null;

        return '<span aria-hidden="true">'.$emoji.'</span>';
    }


                if ($baseline !== null) {
                    MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'mounted', [
                        'widget' => 'weather.forecast',
                        'phase' => 'mount',
                        'location' => $this->location,
                        'forecast_days' => 0,
                        'payload' => MemoryProbe::inspect($this->forecast, 'forecast'),
                    ]);
                }
    public function render()
    {
        return view('weather::dashboard.widget');

        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'mounted', [
                'widget' => 'weather.forecast',
                'phase' => 'mount',
                'location' => $this->location,
                'forecast_days' => \count($this->forecast),
                'payload' => MemoryProbe::inspect($this->forecast, 'forecast'),
                'largest_items' => MemoryProbe::largestItems($this->forecast, 5),
            ]);
        }
    }
    
    protected function mapConditionToIcon(?string $condition): string
    {

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
