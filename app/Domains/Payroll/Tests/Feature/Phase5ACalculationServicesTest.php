<?php

use App\Domains\Payroll\Data\OvertimeBreakdown;
use App\Domains\Payroll\Enums\OvertimeRule;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Services\OvertimeCalculationService;
use App\Domains\Payroll\Services\PayPeriodService;
use App\Domains\Payroll\Services\PayrollRateResolutionService;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;

// ─── PayPeriodService ─────────────────────────────────────────────────────────

describe('PayPeriodService', function (): void {
    it('identifies the Saturday start of a Saturday', function (): void {
        $service = new PayPeriodService;
        $sat = Carbon::parse('2025-04-05'); // known Saturday
        expect($service->periodStartFor($sat)->toDateString())->toBe('2025-04-05');
    });

    it('walks back to Saturday from a Wednesday', function (): void {
        $service = new PayPeriodService;
        $wed = Carbon::parse('2025-04-09'); // Wednesday
        expect($service->periodStartFor($wed)->toDateString())->toBe('2025-04-05');
    });

    it('walks back to Saturday from a Friday', function (): void {
        $service = new PayPeriodService;
        $fri = Carbon::parse('2025-04-11'); // Friday
        expect($service->periodStartFor($fri)->toDateString())->toBe('2025-04-05');
    });

    it('returns true for a date within the current period', function (): void {
        $service = new PayPeriodService;
        $today = Carbon::now();
        expect($service->isWithinCurrentOrPriorPeriod($today))->toBeTrue();
    });

    it('returns true for a date in the prior period', function (): void {
        $service = new PayPeriodService;
        $lastWeek = Carbon::now()->subWeek();
        expect($service->isWithinCurrentOrPriorPeriod($lastWeek))->toBeTrue();
    });

    it('returns false for a date older than two pay periods', function (): void {
        $service = new PayPeriodService;
        $old = Carbon::now()->subWeeks(3);
        expect($service->isWithinCurrentOrPriorPeriod($old))->toBeFalse();
    });

    it('cut-off has not passed for a current-period date', function (): void {
        $service = new PayPeriodService;
        $today = Carbon::now();
        // A date in the current period never triggers the cut-off flag.
        expect($service->isBeyondCutOff($today))->toBeFalse();
    });
});

// ─── PayrollRateResolutionService ────────────────────────────────────────────

describe('PayrollRateResolutionService', function (): void {
    it('resolves a global rate when no project-specific rate exists', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();

        $rate = PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolve($profile, 'standard', null, Carbon::now());

        expect($resolved?->id)->toBe($rate->id);
    });

    it('prefers a project-specific rate over the global rate', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();
        $project = Project::factory()->create();

        // Global rate
        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        // Project-specific rate
        $projectRate = PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => $project->id,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolve($profile, 'standard', $project->id, Carbon::now());

        expect($resolved?->id)->toBe($projectRate->id);
    });

    it('returns null when no applicable rate exists', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolve($profile, 'standard', null, Carbon::now());

        expect($resolved)->toBeNull();
    });

    it('ignores a rate whose effective_date is in the future', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();

        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->addMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolve($profile, 'standard', null, Carbon::now());

        expect($resolved)->toBeNull();
    });

    it('ignores a rate that has expired before the work date', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();

        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->subYear()->toDateString(),
            'expiration_date' => now()->subDay()->toDateString(),
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolve($profile, 'standard', null, Carbon::now());

        expect($resolved)->toBeNull();
    });

    it('returns false for hasAnyRate when no standard rate exists', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create();
        PayRateType::factory()->standard()->create();

        $service = new PayrollRateResolutionService;

        expect($service->hasAnyRate($profile, null, Carbon::now()))->toBeFalse();
    });

    it('returns true for hasAnyRate when a standard rate is active', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();

        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;

        expect($service->hasAnyRate($profile, null, Carbon::now()))->toBeTrue();
    });

    it('resolves using project-assigned pay rate type when present', function (): void {
        $standard = PayRateType::factory()->standard()->create();
        $prevailingBase = PayRateType::factory()->prevailingBase()->create();
        $profile = PayrollEmployeeProfile::factory()->create();
        $project = Project::factory()->create(['pay_rate_type_id' => $prevailingBase->id]);

        // Standard rate exists but project should resolve prevailing base.
        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $standard->id,
            'project_id' => null,
            'rate_amount' => 40.0000,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $projectTypeRate = PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $prevailingBase->id,
            'project_id' => null,
            'rate_amount' => 55.0000,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolveForProject($profile, (string) $project->id, Carbon::now());

        expect($resolved?->id)->toBe($projectTypeRate->id)
            ->and((float) ($resolved?->rate_amount ?? 0.0))->toBe(55.0);
    });

    it('falls back to standard when project has no assigned type', function (): void {
        $standard = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create();
        $project = Project::factory()->create(['pay_rate_type_id' => null]);

        $standardRate = PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $standard->id,
            'project_id' => null,
            'rate_amount' => 42.0000,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $service = new PayrollRateResolutionService;
        $resolved = $service->resolveForProject($profile, (string) $project->id, Carbon::now());

        expect($resolved?->id)->toBe($standardRate->id)
            ->and((float) ($resolved?->rate_amount ?? 0.0))->toBe(42.0);
    });
});

// ─── OvertimeCalculationService – Weekly FLSA ────────────────────────────────

describe('OvertimeCalculationService – Weekly FLSA', function (): void {
    it('treats all hours as regular when under 40 for the week', function (): void {
        $entries = collect([
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-07']),
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-08']),
        ]);
        // Assign predictable string ids for keying
        foreach ($entries as $i => $e) {
            $e->id = 'entry-'.$i;
        }

        $service = new OvertimeCalculationService;
        $result = $service->calculate($entries, OvertimeRule::WeeklyFlsa);

        expect($result['entry-0']->regularHours)->toBe(8.0)
            ->and($result['entry-0']->overtimeHours)->toBe(0.0)
            ->and($result['entry-1']->regularHours)->toBe(8.0)
            ->and($result['entry-1']->overtimeHours)->toBe(0.0);
    });

    it('splits hours exactly at the 40-hour boundary', function (): void {
        // 5 days × 8 h = 40 h regular, then one more day of 4 h = all overtime
        $entries = collect([
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-07']),
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-08']),
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-09']),
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-10']),
            TimecardEntry::factory()->make(['hours' => 8, 'date' => '2025-04-11']),
            TimecardEntry::factory()->make(['hours' => 4, 'date' => '2025-04-12']),
        ]);
        foreach ($entries as $i => $e) {
            $e->id = 'e-'.$i;
        }

        $service = new OvertimeCalculationService;
        $result = $service->calculate($entries, OvertimeRule::WeeklyFlsa);

        // First 5 days all regular
        for ($i = 0; $i < 5; $i++) {
            expect($result["e-{$i}"]->regularHours)->toBe(8.0)
                ->and($result["e-{$i}"]->overtimeHours)->toBe(0.0);
        }
        // Sixth day: all overtime
        expect($result['e-5']->regularHours)->toBe(0.0)
            ->and($result['e-5']->overtimeHours)->toBe(4.0);
    });

    it('splits a single day that straddles the 40-hour mark', function (): void {
        $entries = collect([
            TimecardEntry::factory()->make(['hours' => 38, 'date' => '2025-04-07']),
            TimecardEntry::factory()->make(['hours' => 6, 'date' => '2025-04-08']),
        ]);
        foreach ($entries as $i => $e) {
            $e->id = 'e-'.$i;
        }

        $service = new OvertimeCalculationService;
        $result = $service->calculate($entries, OvertimeRule::WeeklyFlsa);

        expect($result['e-0']->regularHours)->toBe(38.0)
            ->and($result['e-0']->overtimeHours)->toBe(0.0)
            ->and($result['e-1']->regularHours)->toBe(2.0)
            ->and($result['e-1']->overtimeHours)->toBe(4.0);
    });
});

// ─── OvertimeCalculationService – California Daily ───────────────────────────

describe('OvertimeCalculationService – California Daily', function (): void {
    it('keeps all hours as regular when at or under 8', function (): void {
        $entry = TimecardEntry::factory()->make(['hours' => 7.5, 'date' => '2025-04-07']);
        $entry->id = 'e-0';

        $service = new OvertimeCalculationService;
        $result = $service->calculate(collect([$entry]), OvertimeRule::CaliforniaDaily);

        expect($result['e-0']->regularHours)->toBe(7.5)
            ->and($result['e-0']->overtimeHours)->toBe(0.0)
            ->and($result['e-0']->doubleTimeHours)->toBe(0.0);
    });

    it('splits correctly between 8 and 12 hours', function (): void {
        $entry = TimecardEntry::factory()->make(['hours' => 10, 'date' => '2025-04-07']);
        $entry->id = 'e-0';

        $service = new OvertimeCalculationService;
        $result = $service->calculate(collect([$entry]), OvertimeRule::CaliforniaDaily);

        expect($result['e-0']->regularHours)->toBe(8.0)
            ->and($result['e-0']->overtimeHours)->toBe(2.0)
            ->and($result['e-0']->doubleTimeHours)->toBe(0.0);
    });

    it('splits correctly above 12 hours', function (): void {
        $entry = TimecardEntry::factory()->make(['hours' => 14, 'date' => '2025-04-07']);
        $entry->id = 'e-0';

        $service = new OvertimeCalculationService;
        $result = $service->calculate(collect([$entry]), OvertimeRule::CaliforniaDaily);

        expect($result['e-0']->regularHours)->toBe(8.0)
            ->and($result['e-0']->overtimeHours)->toBe(4.0)
            ->and($result['e-0']->doubleTimeHours)->toBe(2.0);
    });

    it('applies 7th-day rule on the seventh consecutive worked day', function (): void {
        // 7 consecutive days starting Saturday
        $dates = ['2025-04-05', '2025-04-06', '2025-04-07', '2025-04-08', '2025-04-09', '2025-04-10', '2025-04-11'];
        $entries = collect($dates)->map(fn (string $date, int $i): TimecardEntry => tap(
            TimecardEntry::factory()->make(['hours' => 9, 'date' => $date]),
            fn ($e) => $e->id = "e-{$i}"
        ));

        $service = new OvertimeCalculationService;
        $result = $service->calculate($entries, OvertimeRule::CaliforniaDaily);

        // Days 0–5 use normal daily split: 8 regular + 1 OT
        for ($i = 0; $i < 6; $i++) {
            expect($result["e-{$i}"]->regularHours)->toBe(8.0)
                ->and($result["e-{$i}"]->overtimeHours)->toBe(1.0)
                ->and($result["e-{$i}"]->doubleTimeHours)->toBe(0.0);
        }

        // 7th day: all overtime (no regular), then DT after 8
        expect($result['e-6']->regularHours)->toBe(0.0)
            ->and($result['e-6']->overtimeHours)->toBe(8.0)
            ->and($result['e-6']->doubleTimeHours)->toBe(1.0);
    });

    it('does not apply 7th-day rule when days are not consecutive', function (): void {
        // 6 days with a gap — 7th entry is NOT consecutive
        $dates = ['2025-04-05', '2025-04-06', '2025-04-07', '2025-04-08', '2025-04-09', '2025-04-10', '2025-04-12'];
        $entries = collect($dates)->map(fn (string $date, int $i): TimecardEntry => tap(
            TimecardEntry::factory()->make(['hours' => 9, 'date' => $date]),
            fn ($e) => $e->id = "e-{$i}"
        ));

        $service = new OvertimeCalculationService;
        $result = $service->calculate($entries, OvertimeRule::CaliforniaDaily);

        // 7th entry should use normal daily split, not 7th-day rule
        expect($result['e-6']->regularHours)->toBe(8.0)
            ->and($result['e-6']->overtimeHours)->toBe(1.0);
    });
});

// ─── OvertimeBreakdown helpers ────────────────────────────────────────────────

describe('OvertimeBreakdown', function (): void {
    it('totalHours sums all tiers', function (): void {
        $bd = new OvertimeBreakdown(8.0, 2.0, 1.5);
        expect($bd->totalHours())->toBe(11.5);
    });
});
