<?php

namespace App\Core\WeatherApi\Listeners;

use App\Core\WeatherApi\Services\WeatherApiService;
use App\Domains\Projects\Events\ProjectAddressChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WarmProjectWeatherLocation implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public int $tries = 2;

    public function handle(ProjectAddressChanged $event): void
    {
        $project = $event->project->loadMissing('address');
        $address = $project->address;

        if ($address === null) {
            return;
        }

        $location = trim(implode(', ', array_filter([
            $address->address1,
            $address->city,
            $address->state,
            $address->zip,
        ], fn (?string $value): bool => filled($value))));

        if ($location === '') {
            return;
        }

        app(WeatherApiService::class)->warmStoredWeatherForLocation($location);
    }
}
