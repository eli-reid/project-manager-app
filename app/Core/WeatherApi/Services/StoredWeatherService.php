<?php

namespace App\Core\WeatherApi\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class StoredWeatherService
{
    public function findCurrentPayload(string $location, int $freshForMinutes): ?array
    {
        $record = $this->baseQuery($location, 'current')
            ->whereDate('weather_date', Carbon::today()->toDateString())
            ->where('synced_at', '>=', now()->subMinutes(max(1, $freshForMinutes)))
            ->orderByDesc('synced_at')
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'location' => $this->locationPayload($record->location_name, $record->source_location),
            'current' => $this->currentPayloadBlock($record),
        ];
    }

    public function findHistoricalPayload(string $location, CarbonInterface $date): ?array
    {
        $record = $this->baseQuery($location, 'history')
            ->whereDate('weather_date', $date->toDateString())
            ->orderByDesc('synced_at')
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'location' => $this->locationPayload($record->location_name, $record->source_location),
            'forecast' => [
                'forecastday' => [$this->forecastDayPayload($record)],
            ],
        ];
    }

    public function findForecastPayload(string $location, CarbonInterface $date): ?array
    {
        $startDate = $date->toDateString();
        $endDate = $date->copy()->addDays(4)->toDateString();

        $records = $this->baseQuery($location, 'forecast')
            ->whereBetween('weather_date', [$startDate, $endDate])
            ->orderBy('weather_date')
            ->get();

        if ($records->isEmpty() || ! $records->contains(fn (object $record): bool => $record->weather_date === $startDate)) {
            return null;
        }

        $firstRecord = $records->first();

        if ($firstRecord === null) {
            return null;
        }

        $payload = [
            'location' => $this->locationPayload($firstRecord->location_name, $firstRecord->source_location),
            'forecast' => [
                'forecastday' => $records->map(fn (object $record): array => $this->forecastDayPayload($record))->all(),
            ],
            'requested_date' => $startDate,
        ];

        $currentRecord = $this->baseQuery($location, 'current')
            ->whereDate('weather_date', Carbon::today()->toDateString())
            ->orderByDesc('synced_at')
            ->first();

        if ($currentRecord !== null) {
            $payload['current'] = $this->currentPayloadBlock($currentRecord);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeCurrentPayload(string $location, array $payload): void
    {
        if (! is_array($payload['current'] ?? null)) {
            return;
        }

        $weatherDate = $this->resolveWeatherDate($payload, Carbon::today());
        $locationName = $this->extractLocationName($payload, $location);
        $current = $payload['current'];

        $this->upsertRecords([[
            'location_key' => $this->normalizeLocationKey($location),
            'source_location' => $location,
            'location_name' => $locationName,
            'record_type' => 'current',
            'weather_date' => $weatherDate->toDateString(),
            'temperature' => $current['temp_f'] ?? null,
            'temperature_high' => null,
            'temperature_low' => null,
            'temperature_unit' => 'F',
            'wind_speed' => $current['wind_mph'] ?? null,
            'wind_direction' => $current['wind_dir'] ?? null,
            'precipitation' => $current['precip_in'] ?? null,
            'humidity' => $current['humidity'] ?? null,
            'condition_text' => $current['condition']['text'] ?? null,
            'weather_icon' => $current['condition']['icon'] ?? null,
        ]]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeHistoricalPayload(string $location, CarbonInterface $date, array $payload): void
    {
        $forecastDay = $payload['forecast']['forecastday'][0] ?? null;

        if (! is_array($forecastDay) || ! is_array($forecastDay['day'] ?? null)) {
            return;
        }

        $day = $forecastDay['day'];
        $locationName = $this->extractLocationName($payload, $location);

        $this->upsertRecords([[
            'location_key' => $this->normalizeLocationKey($location),
            'source_location' => $location,
            'location_name' => $locationName,
            'record_type' => 'history',
            'weather_date' => ($forecastDay['date'] ?? $date->toDateString()),
            'temperature' => $day['avgtemp_f'] ?? null,
            'temperature_high' => $day['maxtemp_f'] ?? null,
            'temperature_low' => $day['mintemp_f'] ?? null,
            'temperature_unit' => 'F',
            'wind_speed' => $day['maxwind_mph'] ?? null,
            'wind_direction' => null,
            'precipitation' => $day['totalprecip_in'] ?? null,
            'humidity' => $day['avghumidity'] ?? null,
            'condition_text' => $day['condition']['text'] ?? null,
            'weather_icon' => $day['condition']['icon'] ?? null,
        ]]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeForecastPayload(string $location, array $payload): int
    {
        $forecastDays = $payload['forecast']['forecastday'] ?? null;

        if (! is_array($forecastDays)) {
            return 0;
        }

        $locationName = $this->extractLocationName($payload, $location);
        $records = [];

        if (is_array($payload['current'] ?? null)) {
            $weatherDate = $this->resolveWeatherDate($payload, Carbon::today());
            $current = $payload['current'];

            $records[] = [
                'location_key' => $this->normalizeLocationKey($location),
                'source_location' => $location,
                'location_name' => $locationName,
                'record_type' => 'current',
                'weather_date' => $weatherDate->toDateString(),
                'temperature' => $current['temp_f'] ?? null,
                'temperature_high' => null,
                'temperature_low' => null,
                'temperature_unit' => 'F',
                'wind_speed' => $current['wind_mph'] ?? null,
                'wind_direction' => $current['wind_dir'] ?? null,
                'precipitation' => $current['precip_in'] ?? null,
                'humidity' => $current['humidity'] ?? null,
                'condition_text' => $current['condition']['text'] ?? null,
                'weather_icon' => $current['condition']['icon'] ?? null,
            ];
        }

        foreach ($forecastDays as $forecastDay) {
            if (! is_array($forecastDay) || ! is_array($forecastDay['day'] ?? null)) {
                continue;
            }

            $day = $forecastDay['day'];
            $records[] = [
                'location_key' => $this->normalizeLocationKey($location),
                'source_location' => $location,
                'location_name' => $locationName,
                'record_type' => 'forecast',
                'weather_date' => (string) ($forecastDay['date'] ?? Carbon::today()->toDateString()),
                'temperature' => $day['avgtemp_f'] ?? null,
                'temperature_high' => $day['maxtemp_f'] ?? null,
                'temperature_low' => $day['mintemp_f'] ?? null,
                'temperature_unit' => 'F',
                'wind_speed' => $day['maxwind_mph'] ?? null,
                'wind_direction' => null,
                'precipitation' => $day['totalprecip_in'] ?? null,
                'humidity' => $day['avghumidity'] ?? null,
                'condition_text' => $day['condition']['text'] ?? null,
                'weather_icon' => $day['condition']['icon'] ?? null,
            ];
        }

        $this->upsertRecords($records);

        return count($records);
    }

    public function hasRecord(string $location, CarbonInterface $date, string $type): bool
    {
        return $this->baseQuery($location, $type)
            ->whereDate('weather_date', $date->toDateString())
            ->exists();
    }

    public function pruneExpiredRecords(int $retentionDays): int
    {
        $cutoffDate = Carbon::today()->subDays(max(1, $retentionDays))->toDateString();

        return DB::table('weather_records')
            ->whereDate('weather_date', '<', $cutoffDate)
            ->delete();
    }

    protected function baseQuery(string $location, string $type)
    {
        return DB::table('weather_records')
            ->where('location_key', $this->normalizeLocationKey($location))
            ->where('record_type', $type);
    }

    protected function normalizeLocationKey(string $location): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $location) ?? $location));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractLocationName(array $payload, string $fallback): string
    {
        if (! is_array($payload['location'] ?? null)) {
            return $fallback;
        }

        $locationName = (string) ($payload['location']['name'] ?? '');
        $region = (string) ($payload['location']['region'] ?? '');
        $country = (string) ($payload['location']['country'] ?? '');
        $suffix = $region !== '' ? $region : $country;

        return trim($locationName.($suffix !== '' ? ', '.$suffix : '')) ?: $fallback;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveWeatherDate(array $payload, CarbonInterface $default): CarbonInterface
    {
        $localtime = $payload['location']['localtime'] ?? null;

        if (is_string($localtime) && $localtime !== '') {
            try {
                return Carbon::parse($localtime);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $default;
    }

    protected function locationPayload(?string $locationName, string $fallback): array
    {
        return [
            'name' => $locationName ?: $fallback,
            'region' => '',
            'country' => '',
        ];
    }

    protected function currentPayloadBlock(object $record): array
    {
        return [
            'temp_f' => $this->floatOrNull($record->temperature),
            'wind_mph' => $this->floatOrNull($record->wind_speed),
            'wind_dir' => $record->wind_direction,
            'precip_in' => $this->floatOrNull($record->precipitation),
            'humidity' => $this->intOrNull($record->humidity),
            'condition' => [
                'text' => $record->condition_text,
                'icon' => $record->weather_icon,
            ],
        ];
    }

    protected function forecastDayPayload(object $record): array
    {
        return [
            'date' => $record->weather_date,
            'day' => [
                'avgtemp_f' => $this->floatOrNull($record->temperature),
                'maxtemp_f' => $this->floatOrNull($record->temperature_high),
                'mintemp_f' => $this->floatOrNull($record->temperature_low),
                'maxwind_mph' => $this->floatOrNull($record->wind_speed),
                'totalprecip_in' => $this->floatOrNull($record->precipitation),
                'avghumidity' => $this->intOrNull($record->humidity),
                'condition' => [
                    'text' => $record->condition_text,
                    'icon' => $record->weather_icon,
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function upsertRecords(array $records): void
    {
        if ($records === []) {
            return;
        }

        $timestamp = now();

        $preparedRecords = array_map(function (array $record) use ($timestamp): array {
            return [
                ...$record,
                'synced_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, $records);

        DB::table('weather_records')->upsert(
            $preparedRecords,
            ['location_key', 'record_type', 'weather_date'],
            ['source_location', 'location_name', 'temperature', 'temperature_high', 'temperature_low', 'temperature_unit', 'wind_speed', 'wind_direction', 'precipitation', 'humidity', 'condition_text', 'weather_icon', 'synced_at', 'updated_at'],
        );
    }

    protected function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
