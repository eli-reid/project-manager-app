<?php

namespace App\Core\WeatherApi\Livewire\Dashboard;

use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Support\Diagnostics\MemoryProbe;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\View;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Component;

class Widget extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $forecast = [];

    public string $location = '';

    public function mount(WeatherApiContract $weatherApi): void
    {
        $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.weather.forecast.mount.start') : null;

        $this->location = (string) Settings::get('weatherapi.default_location')->toNullableString() ?? '';

        if ($this->location === '') {
            $this->forecast = [];

            if ($baseline !== null) {
                MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'mounted', [
                    'widget' => 'weather.forecast',
                    'phase' => 'mount',
                    'location' => $this->location,
                    'forecast_days' => 0,
                    'payload' => MemoryProbe::inspect($this->forecast, 'forecast'),
                ]);
            }

            return;
        }

        $payload = $weatherApi->getForecastWeather($this->location, Carbon::today());
        $items = $this->buildForecastItems($payload);

        $this->forecast = $items;

        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'mounted', [
                'widget' => 'weather.forecast',
                'phase' => 'mount',
                'location' => $this->location,
                'forecast_days' => count($this->forecast),
                'payload' => MemoryProbe::inspect($this->forecast, 'forecast'),
                'largest_items' => MemoryProbe::largestItems($this->forecast, 5),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<int, array<string, mixed>>
     */
    protected function buildForecastItems(?array $payload): array
    {
        $items = [];
        $locationName = $this->extractLocationName($payload);
        $forecastDays = $this->indexForecastDaysByDate($payload);

        for ($i = 0; $i < $this->forecastDays(); $i++) {
            $date = Carbon::today()->addDays($i);
            $dayData = $this->buildForecastDayData($forecastDays[$date->toDateString()] ?? null, $date, $locationName);

            $items[] = $this->buildForecastItem(
                $dayData,
                $this->formatDisplayDate($dayData['date'] ?? null),
                $this->renderIconHtml($dayData['flux_icon'] ?? null),
            );
        }

        return $items;
    }

    protected function formatDisplayDate(?string $date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            return Carbon::parse($date)->format('M j D');
        } catch (\Throwable) {
            return $date;
        }
    }

    /**
     * @param  array<string, mixed>  $dayData
     * @return array<string, mixed>
     */
    protected function buildForecastItem(array $dayData, string $displayDate, string $iconHtml): array
    {
        return [
            'date' => $dayData['date'] ?? null,
            'display_date' => $displayDate,
            'icon_html' => $iconHtml,
            'condition' => $dayData['condition'] ?? null,
            'location_name' => $dayData['location_name'] ?? null,
            'temperature' => $dayData['temperature'] ?? null,
            'temperature_high' => $dayData['temperature_high'] ?? null,
            'temperature_low' => $dayData['temperature_low'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function extractLocationName(?array $payload): ?string
    {
        if (! is_array($payload['location'] ?? null)) {
            return null;
        }

        $locationName = (string) ($payload['location']['name'] ?? '');
        $region = (string) ($payload['location']['region'] ?? '');
        $country = (string) ($payload['location']['country'] ?? '');
        $suffix = $region !== '' ? $region : $country;

        return trim($locationName.($suffix !== '' ? ', '.$suffix : '')) ?: null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, array<string, mixed>>
     */
    protected function indexForecastDaysByDate(?array $payload): array
    {
        $forecastDays = $payload['forecast']['forecastday'] ?? null;

        if (! is_array($forecastDays)) {
            return [];
        }

        $indexedForecastDays = [];

        foreach ($forecastDays as $forecastDay) {
            if (! is_array($forecastDay)) {
                continue;
            }

            $date = $forecastDay['date'] ?? null;

            if (! is_string($date) || $date === '') {
                continue;
            }

            $indexedForecastDays[$date] = $forecastDay;
        }

        return $indexedForecastDays;
    }

    /**
     * @param  array<string, mixed>|null  $forecastDay
     * @return array<string, mixed>
     */
    protected function buildForecastDayData(?array $forecastDay, CarbonInterface $date, ?string $locationName): array
    {
        $day = is_array($forecastDay['day'] ?? null) ? $forecastDay['day'] : [];
        $condition = is_array($day['condition'] ?? null) ? $day['condition'] : [];

        return [
            'date' => $date->toDateString(),
            'condition' => $condition['text'] ?? null,
            'location_name' => $locationName,
            'temperature' => $day['avgtemp_f'] ?? null,
            'temperature_high' => $day['maxtemp_f'] ?? null,
            'temperature_low' => $day['mintemp_f'] ?? null,
            'flux_icon' => $this->mapConditionToIcon($condition['text'] ?? null),
        ];
    }

    protected function renderIconHtml(?string $iconName): string
    {
        if (empty($iconName)) {
            return '';
        }

        if (View::exists('flux.icons.'.$iconName)) {
            return view('flux.icons.'.$iconName, ['attributes' => new ComponentAttributeBag([])])->render();
        }

        $base = explode('-', $iconName)[0] ?? $iconName;
        $emoji = match ($base) {
            'cloud', 'rain', 'snow' => '☁️',
            'bolt', 'lightning' => '⚡️',
            'sun', 'clear' => '☀️',
            default => '🌤️',
        };

        return '<span aria-hidden="true">'.$emoji.'</span>';
    }

    public function render()
    {
        return view('weather::dashboard.widget');
    }

    protected function forecastDays(): int
    {
        return 3;
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
