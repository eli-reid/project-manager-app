<?php

use App\PlugIns\Cpanel\Services\CpanelService;

it('prints cron commands in dry run mode', function (): void {
    $service = Mockery::mock(CpanelService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldNotReceive('ensureCronJob');
    app()->instance(CpanelService::class, $service);

    $this->artisan('cpanel:ensure-laravel-cron --dry-run')
        ->expectsOutputToContain('Scheduler command:')
        ->expectsOutputToContain('Queue command:')
        ->expectsOutputToContain('Dry run complete. No cPanel API calls were made.')
        ->assertSuccessful();
});

it('ensures scheduler and queue cron jobs through cpanel service', function (): void {
    $service = Mockery::mock(CpanelService::class);
    $service->shouldReceive('isConfigured')->once()->andReturn(true);
    $service->shouldReceive('ensureCronJob')->twice()->andReturn([
        'success' => true,
        'action' => 'added',
    ]);
    app()->instance(CpanelService::class, $service);

    $this->artisan('cpanel:ensure-laravel-cron')
        ->expectsOutputToContain('Scheduler cron: added')
        ->expectsOutputToContain('Queue cron: added')
        ->expectsOutputToContain('cPanel cron jobs are configured.')
        ->assertSuccessful();
});
