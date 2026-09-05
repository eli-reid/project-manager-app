<?php

use App\Core\Scheduler\Services\SchedulerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(SchedulerService::class)->run();
})
    ->name('scheduler:run-dynamic-tasks')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('weather:sync-stored-data')
    ->name('weather:sync-stored-data')
    ->hourly()
    ->withoutOverlapping(55);
