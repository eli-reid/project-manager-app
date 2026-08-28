<?php

namespace App\Core\WeatherApi\Console\Commands;

use App\Core\WeatherApi\Services\WeatherApiService;
use Illuminate\Console\Command;

class SyncStoredWeatherData extends Command
{
    protected $signature = 'weather:sync-stored-data';

    protected $description = 'Refresh stored weather data for the configured default location and prune expired records.';

    public function handle(WeatherApiService $weatherApiService): int
    {
        $result = $weatherApiService->syncStoredWeather();

        if (($result['location'] ?? '') === '') {
            $this->warn('No default weather location is configured. Skipped sync.');
            $this->line('Pruned records: '.(string) ($result['pruned_records'] ?? 0));

            return self::SUCCESS;
        }

        $this->info('Stored weather sync completed.');
        $this->line('Location: '.(string) $result['location']);
        $this->line('Synced records: '.(string) ($result['synced_records'] ?? 0));
        $this->line('Pruned records: '.(string) ($result['pruned_records'] ?? 0));

        return self::SUCCESS;
    }
}
