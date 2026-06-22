<?php

use App\Domains\Payroll\Contracts\ApprovedTimecardEntryProvider;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Services\PayrollStatementBuilderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

it('uses the approved timecard entry provider boundary for pay period reads', function (): void {
    $payRun = PayRun::factory()->create([
        'pay_period_start' => '2026-04-12',
        'pay_period_end' => '2026-04-18',
        'pay_date' => '2026-04-25',
    ]);

    $provider = Mockery::mock(ApprovedTimecardEntryProvider::class);
    $provider->shouldReceive('forPayPeriod')
        ->once()
        ->withArgs(function (Carbon $start, Carbon $end): bool {
            return $start->toDateString() === '2026-04-12'
                && $end->toDateString() === '2026-04-18';
        })
        ->andReturn(collect());

    $this->app->instance(ApprovedTimecardEntryProvider::class, $provider);

    $result = app(PayrollStatementBuilderService::class)->buildForRun($payRun);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});
