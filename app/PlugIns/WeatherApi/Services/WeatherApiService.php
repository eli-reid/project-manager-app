<?php

namespace App\PlugIns\WeatherApi\Services;

use App\Core\Settings\Facades\Settings;
use App\PlugIns\WeatherApi\Contracts\WeatherApiContract;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherApiService implements WeatherApiContract
{
    public function getCurrentWeather(string $location): ?array
    {
        return $this->remember($this->cacheKeyCurrent($location), function () use ($location): ?array {
            return $this->makeApiRequest('current.json', [
                'q' => $location,
            ]);
        });
    }

    public function getHistoricalWeather(string $location, CarbonInterface $date): ?array
    {
        $formattedDate = $date->toDateString();

        return $this->remember($this->cacheKeyHistory($location, $formattedDate), function () use ($location, $formattedDate): ?array {
            return $this->makeApiRequest('history.json', [
                'q' => $location,
                'dt' => $formattedDate,
            ]);
        });
    }

    public function getForecastWeather(string $location, CarbonInterface $date): ?array
    {
        if ($date->isToday() || $date->isFuture()) {
            $formattedDate = $date->toDateString();

            return $this->remember($this->cacheKeyForecast($location, $formattedDate), function () use ($location, $formattedDate): ?array {
                return $this->makeApiRequest('forecast.json', [
                    'q' => $location,
                    'dt' => $formattedDate,
                    'days' => 1,
                ]);
            });
        }

        return $this->getHistoricalWeather($location, $date);
    }

    public function getWeatherByIp(string $ipAddress, ?CarbonInterface $date = null): ?array
    {
        $locationData = $this->getLocationFromIp($ipAddress);

        if ($locationData === null || ! isset($locationData['location']['lat'], $locationData['location']['lon'])) {
            return null;
        }

        $location = sprintf('%s,%s', $locationData['location']['lat'], $locationData['location']['lon']);

        return $this->getForecastWeather($location, $date ?? Carbon::today());
    }

    public function getLocationFromIp(string $ipAddress): ?array
    {
        return $this->remember($this->cacheKeyIp($ipAddress), function () use ($ipAddress): ?array {
            return $this->makeApiRequest('ip.json', [
                'q' => $ipAddress,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $weatherData
     * @return array<string, mixed>
     */
    public function extractWeatherForDailyReport(array $weatherData): array
    {
        $result = [
            'condition' => null,
            'temperature' => null,
            'temperature_unit' => 'F',
            'wind_speed' => null,
            'wind_direction' => null,
            'precipitation' => null,
            'humidity' => null,
            'weather_icon' => null,
            'location_name' => null,
            'date' => null,
            'full_data' => $weatherData,
        ];

        if (isset($weatherData['location']) && is_array($weatherData['location'])) {
            $locationName = (string) ($weatherData['location']['name'] ?? '');
            $region = (string) ($weatherData['location']['region'] ?? '');
            $country = (string) ($weatherData['location']['country'] ?? '');
            $suffix = $region !== '' ? $region : $country;
            $result['location_name'] = trim($locationName.($suffix !== '' ? ', '.$suffix : ''));
        }

        if (isset($weatherData['forecast']['forecastday'][0]['day']) && is_array($weatherData['forecast']['forecastday'][0]['day'])) {
            $day = $weatherData['forecast']['forecastday'][0]['day'];

            $result['condition'] = $day['condition']['text'] ?? null;
            $result['temperature'] = $day['avgtemp_f'] ?? null;
            $result['wind_speed'] = $day['maxwind_mph'] ?? null;
            $result['precipitation'] = $day['totalprecip_in'] ?? null;
            $result['humidity'] = $day['avghumidity'] ?? null;
            $result['weather_icon'] = $day['condition']['icon'] ?? null;
            $result['date'] = $weatherData['forecast']['forecastday'][0]['date'] ?? null;
        }

        if (isset($weatherData['current']) && is_array($weatherData['current'])) {
            $current = $weatherData['current'];

            $result['condition'] = $current['condition']['text'] ?? null;
            $result['temperature'] = $current['temp_f'] ?? null;
            $result['wind_speed'] = $current['wind_mph'] ?? null;
            $result['wind_direction'] = $current['wind_dir'] ?? null;
            $result['precipitation'] = $current['precip_in'] ?? null;
            $result['humidity'] = $current['humidity'] ?? null;
            $result['weather_icon'] = $current['condition']['icon'] ?? null;

            if (isset($weatherData['location']['localtime']) && is_string($weatherData['location']['localtime'])) {
                try {
                    $result['date'] = Carbon::parse($weatherData['location']['localtime'])->toDateString();
                } catch (Throwable) {
                    $result['date'] = Carbon::today()->toDateString();
                }
            }
        }

        return $result;
    }

    public function hasStoredWeatherData(string $location, CarbonInterface $date, string $type = 'forecast'): bool
    {
        $formattedDate = $date->toDateString();

        $cacheKey = match ($type) {
            'current' => $this->cacheKeyCurrent($location),
            'history' => $this->cacheKeyHistory($location, $formattedDate),
            'forecast' => $this->cacheKeyForecast($location, $formattedDate),
            'ip' => $this->cacheKeyIp($location),
            default => $this->cacheKeyForecast($location, $formattedDate),
        };

        return Cache::has($cacheKey);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    protected function makeApiRequest(string $endpoint, array $params = []): ?array
    {
        $apiKey = $this->apiKey();

        if ($apiKey === '') {
            Log::warning('Weather API key is not configured.', ['endpoint' => $endpoint]);

            return null;
        }

        $requestParams = [...$params, 'key' => $apiKey];

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->retry(2, 200)
                ->get($this->baseUrl().'/'.$endpoint, $requestParams);

            if ($response->successful()) {
                $payload = $response->json();

                return is_array($payload) ? $payload : null;
            }

            Log::warning('Weather API request failed.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (Throwable $exception) {
            Log::error('Weather API request exception.', [
                'endpoint' => $endpoint,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @template T
     *
     * @param  callable():T  $resolver
     * @return T
     */
    protected function remember(string $key, callable $resolver): mixed
    {
        return Cache::remember($key, now()->addMinutes($this->cacheDurationMinutes()), $resolver);
    }

    protected function apiKey(): string
    {
        return $this->stringSetting('weatherapi.key', 'services.weatherapi.key', '');
    }

    protected function baseUrl(): string
    {
        return rtrim($this->stringSetting('weatherapi.base_url', 'services.weatherapi.base_url', 'https://api.weatherapi.com/v1'), '/');
    }

    protected function cacheDurationMinutes(): int
    {
        return $this->positiveIntSetting('weatherapi.cache_duration', 'services.weatherapi.cache_duration', 60);
    }

    protected function timeoutSeconds(): int
    {
        return $this->positiveIntSetting('weatherapi.timeout', 'services.weatherapi.timeout', 10);
    }

    protected function stringSetting(string $settingKey, string $configKey, string $default): string
    {
        $value = Settings::get($settingKey, null)->raw();

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return (string) config($configKey, $default);
    }

    protected function positiveIntSetting(string $settingKey, string $configKey, int $default): int
    {
        $value = Settings::get($settingKey, null)->raw();

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return max(1, (int) config($configKey, $default));
    }

    protected function cacheKeyCurrent(string $location): string
    {
        return sprintf('weather_current_%s', $location);
    }

    protected function cacheKeyHistory(string $location, string $formattedDate): string
    {
        return sprintf('weather_history_%s_%s', $location, $formattedDate);
    }

    protected function cacheKeyForecast(string $location, string $formattedDate): string
    {
        return sprintf('weather_forecast_%s_%s', $location, $formattedDate);
    }

    protected function cacheKeyIp(string $ipAddress): string
    {
        return sprintf('location_ip_%s', $ipAddress);
    }
}
