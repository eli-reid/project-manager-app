<?php

use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Services\PayrollReportingService;
use Illuminate\Support\Carbon;

it('uses the timecard read gateway when building labor cost rows', function (): void {
    $gateway = \Mockery::mock(PayrollTimecardReadGateway::class);
    $gateway->shouldReceive('approvedEntriesForDateRange')
        ->once()
        ->withArgs(function (Carbon $start, Carbon $end, ?string $projectId, array $statuses, array $with): bool {
            return $start->toDateString() === '2026-04-01'
                && $end->toDateString() === '2026-04-30'
                && $projectId === null
                && $statuses === []
                && $with !== [];
        })
        ->andReturn(collect());

    $this->app->instance(PayrollTimecardReadGateway::class, $gateway);

    $rows = app(PayrollReportingService::class)->laborCostRows(null, '2026-04-01', '2026-04-30');

    expect($rows)->toBeArray()->toHaveCount(0);
});
