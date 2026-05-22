<?php

use App\Domains\Timecards\Services\TimecardReminderService;

it('runs timecard reminders immediately with provided options', function (): void {
    $service = Mockery::mock(TimecardReminderService::class);
    $service->shouldReceive('sendPendingReminderNotifications')
        ->once()
        ->with([
            'days_after_week_end' => 2,
            'batch_size' => 25,
            'statuses' => ['draft', 'rejected'],
        ])
        ->andReturn(3);

    app()->instance(TimecardReminderService::class, $service);

    $this->artisan('timecards:reminders:run --days-after-week-end=2 --batch-size=25 --statuses=draft,rejected')
        ->expectsOutputToContain('Running timecard reminders now...')
        ->expectsOutputToContain('Timecard reminders run completed.')
        ->expectsOutputToContain('Sent reminders: 3')
        ->assertSuccessful();
});

it('falls back to default statuses when statuses option is invalid', function (): void {
    $service = Mockery::mock(TimecardReminderService::class);
    $service->shouldReceive('sendPendingReminderNotifications')
        ->once()
        ->with([
            'days_after_week_end' => 0,
            'batch_size' => 10,
            'statuses' => ['draft', 'rejected'],
        ])
        ->andReturn(0);

    app()->instance(TimecardReminderService::class, $service);

    $this->artisan('timecards:reminders:run --statuses=foo,bar')
        ->expectsOutputToContain('Sent reminders: 0')
        ->assertSuccessful();
});
