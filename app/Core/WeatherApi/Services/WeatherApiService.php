<?php

namespace App\Core\WeatherApi\Services;

use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherApiService implements WeatherApiContract
{
    public function __construct(protected StoredWeatherService $storedWeatherService) {}

    public function getCurrentWeather(string $location): ?array
    {
        $storedPayload = $this->storedWeatherService->findCurrentPayload($location, $this->cacheDurationMinutes());

        if ($storedPayload !== null) {
            return $storedPayload;
        }

        $this->bootstrapLocationWeatherIfMissing($location);

        $storedPayload = $this->storedWeatherService->findCurrentPayload($location, $this->cacheDurationMinutes());

        if ($storedPayload !== null) {
            return $storedPayload;
        }

        return $this->remember($this->cacheKeyCurrent($location), function () use ($location): ?array {
            $payload = $this->makeApiRequest('current.json', [
                'q' => $location,
            ]);

            if (is_array($payload)) {
                $this->storedWeatherService->storeCurrentPayload($location, $payload);
            }

            return $payload;
        });
    }

    public function getHistoricalWeather(string $location, CarbonInterface $date): ?array
    {
        $formattedDate = $date->toDateString();

        $storedPayload = $this->storedWeatherService->findHistoricalPayload($location, $date);

        if ($storedPayload !== null) {
            return $storedPayload;
        }

        return $this->remember($this->cacheKeyHistory($location, $formattedDate), function () use ($location, $formattedDate): ?array {
            $payload = $this->makeApiRequest('history.json', [
                'q' => $location,
                'dt' => $formattedDate,
            ]);

            if (is_array($payload)) {
                $this->storedWeatherService->storeHistoricalPayload($location, Carbon::parse($formattedDate), $payload);
            }

            return $payload;
        });
    }

    public function getForecastWeather(string $location, CarbonInterface $date): ?array
    {
        if ($date->isToday() || $date->isFuture()) {
            $formattedDate = $date->toDateString();

            $storedPayload = $this->storedWeatherService->findForecastPayload($location, $date);

            if ($storedPayload !== null && $this->hasCompleteForecast($storedPayload, $date)) {
                return $storedPayload;
            }

            return $this->remember($this->cacheKeyForecast($location, $formattedDate), function () use ($location, $formattedDate): ?array {
                $payload = $this->makeApiRequest('forecast.json', [
                    'q' => $location,
                    'days' => 5,
                ]);

                if (is_array($payload)) {
                    $this->storedWeatherService->storeForecastPayload($location, $payload);

                    // Include the originally requested date so downstream extractors
                    // can select the proper day from the multi-day forecast.
                    $payload['requested_date'] = $formattedDate;
                }

                return $payload;
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
            'temperature_high' => null,
            'temperature_low' => null,
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

        if (isset($weatherData['forecast']['forecastday']) && is_array($weatherData['forecast']['forecastday'])) {
            $forecastDays = $weatherData['forecast']['forecastday'];

            // Try to locate the requested date if provided (multi-day forecast payload)
            $targetDate = $weatherData['requested_date'] ?? null;
            $day = null;

            if ($targetDate !== null) {
                foreach ($forecastDays as $fd) {
                    if (isset($fd['date']) && $fd['date'] === $targetDate) {
                        $day = $fd['day'] ?? null;
                        $result['date'] = $fd['date'];
                        break;
                    }
                }
            }

            // Fallback: use the first forecast day
            if ($day === null && isset($forecastDays[0]['day']) && is_array($forecastDays[0]['day'])) {
                $day = $forecastDays[0]['day'];
                $result['date'] = $forecastDays[0]['date'] ?? $result['date'];
            }

            if (is_array($day)) {
                $result['condition'] = $day['condition']['text'] ?? null;
                $result['temperature'] = $day['avgtemp_f'] ?? null;
                $result['temperature_high'] = $day['maxtemp_f'] ?? null;
                $result['temperature_low'] = $day['mintemp_f'] ?? null;
                $result['wind_speed'] = $day['maxwind_mph'] ?? null;
                $result['precipitation'] = $day['totalprecip_in'] ?? null;
                $result['humidity'] = $day['avghumidity'] ?? null;
                $result['weather_icon'] = $day['condition']['icon'] ?? null;
            }
        }

        // Only use the `current` block when the requested date is today, or when
        // no forecast day data was available. This prevents the `current` data
        // (which reflects now) from overwriting multi-day forecast entries.
        $targetDate = $weatherData['requested_date'] ?? null;
        $isRequestedToday = $targetDate !== null && $targetDate === Carbon::today()->toDateString();

        if (isset($weatherData['current']) && is_array($weatherData['current']) && ($isRequestedToday || $result['condition'] === null)) {
            $current = $weatherData['current'];

            $result['condition'] = $current['condition']['text'] ?? $result['condition'];
            $result['temperature'] = $current['temp_f'] ?? $result['temperature'];
            $result['wind_speed'] = $current['wind_mph'] ?? $result['wind_speed'];
            $result['wind_direction'] = $current['wind_dir'] ?? $result['wind_direction'];
            $result['precipitation'] = $current['precip_in'] ?? $result['precipitation'];
            $result['humidity'] = $current['humidity'] ?? $result['humidity'];
            $result['weather_icon'] = $current['condition']['icon'] ?? $result['weather_icon'];

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
        return $this->storedWeatherService->hasRecord($location, $date, $type);
    }

    /**
     * @return array{location:string, synced_records:int, pruned_records:int}
     */
    public function syncStoredWeather(): array
    {
        $location = $this->defaultLocation();
        $prunedRecords = $this->pruneStoredWeather();

        if ($location === '') {
            return [
                'location' => '',
                'synced_records' => 0,
                'pruned_records' => $prunedRecords,
            ];
        }

        $syncedRecords = $this->syncLocationWeatherBackfill($location);

        return [
            'location' => $location,
            'synced_records' => $syncedRecords,
            'pruned_records' => $prunedRecords,
        ];
    }

    protected function bootstrapLocationWeatherIfMissing(string $location): void
    {
        if ($this->storedWeatherService->hasAnyRecordsForLocation($location)) {
            return;
        }

        $this->syncLocationWeatherBackfill($location);
    }

    protected function syncLocationWeatherBackfill(string $location): int
    {
        $forecastPayload = $this->makeApiRequest('forecast.json', [
            'q' => $location,
            'days' => 5,
        ]);

        $syncedRecords = 0;

        if (is_array($forecastPayload)) {
            $syncedRecords += $this->storedWeatherService->storeForecastPayload($location, $forecastPayload);
        }

        $historyDaysToSync = max(1, $this->retentionDays());

        for ($offset = 1; $offset <= $historyDaysToSync; $offset++) {
            $historyDate = Carbon::today()->subDays($offset);
            $historyPayload = $this->makeApiRequest('history.json', [
                'q' => $location,
                'dt' => $historyDate->toDateString(),
            ]);

            if (! is_array($historyPayload)) {
                continue;
            }

            $this->storedWeatherService->storeHistoricalPayload($location, $historyDate, $historyPayload);
            $syncedRecords++;
        }

        return $syncedRecords;
    }

    public function pruneStoredWeather(): int
    {
        return $this->storedWeatherService->pruneExpiredRecords($this->retentionDays());
    }

    public function warmStoredWeatherForLocation(string $location): void
    {
        $this->bootstrapLocationWeatherIfMissing($location);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function hasCompleteForecast(array $payload, CarbonInterface $date): bool
    {
        $forecastDays = $payload['forecast']['forecastday'] ?? null;

        if (! is_array($forecastDays)) {
            return false;
        }

        $availableDates = collect($forecastDays)
            ->pluck('date')
            ->filter(fn (mixed $forecastDate): bool => is_string($forecastDate) && $forecastDate !== '');

        for ($offset = 0; $offset < 5; $offset++) {
            if (! $availableDates->contains($date->copy()->addDays($offset)->toDateString())) {
                return false;
            }
        }

        return true;
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

    protected function retentionDays(): int
    {
        return $this->positiveIntSetting('weatherapi.retention_days', 'services.weatherapi.retention_days', 30);
    }

    protected function defaultLocation(): string
    {
        return $this->stringSetting('weatherapi.default_location', 'services.weatherapi.default_location', '');
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
