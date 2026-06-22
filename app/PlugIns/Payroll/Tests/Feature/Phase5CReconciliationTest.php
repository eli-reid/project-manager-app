<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Payroll\Data\ReconciliationRow;
use App\Domains\Payroll\Services\TimecardDailyReconciliationService;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;

function reconciliationService(): TimecardDailyReconciliationService
{
    return new TimecardDailyReconciliationService;
}

function enableReconciliation(bool $requireProject = false, float $tolerance = 0.25): void
{
    // Seed settings via the Settings facade.
    Settings::set('payroll.reconciliation.enabled', 'true');
    Settings::set('payroll.reconciliation.hours_tolerance', (string) $tolerance);
    Settings::set('payroll.reconciliation.require_project_match', $requireProject ? 'true' : 'false');
    Settings::set('payroll.reconciliation.require_cost_code_match', 'false');
    Settings::set('payroll.reconciliation.warning_only', 'true');
    Settings::clearAllCache();
}

// ─── Reconciliation disabled ──────────────────────────────────────────────────

describe('reconciliation disabled', function (): void {
    it('returns an empty collection when reconciliation is disabled', function (): void {
        Settings::set('payroll.reconciliation.enabled', 'false');
        Settings::clearAllCache();

        $user = User::factory()->create();

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse('2025-04-07'),
            Carbon::parse('2025-04-11'),
        );

        expect($result)->toBeEmpty();
    });
});

// ─── Hours within tolerance ───────────────────────────────────────────────────

describe('hours within tolerance', function (): void {
    it('marks a row as NOT a mismatch when variance is within tolerance', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $date = '2025-04-07';

        TimecardEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'date' => $date,
            'hours' => 8.0,
        ]);

        DailyReport::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'report_date' => $date,
            'total_hours' => 8.1, // within default 0.25 tolerance
        ]);

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse($date),
            Carbon::parse($date),
        );

        expect($result)->toHaveCount(1);

        /** @var ReconciliationRow $row */
        $row = $result->first();
        expect($row->isMismatch)->toBeFalse()
            ->and($row->variance)->toBeLessThanOrEqual(0.25);
    });
});

// ─── Mismatch detection ───────────────────────────────────────────────────────

describe('mismatch detection', function (): void {
    it('flags a row as a mismatch when variance exceeds tolerance', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $date = '2025-04-07';

        TimecardEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'date' => $date,
            'hours' => 8.0,
        ]);

        DailyReport::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'report_date' => $date,
            'total_hours' => 10.0, // 2 h variance > 0.25
        ]);

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse($date),
            Carbon::parse($date),
        );

        /** @var ReconciliationRow $row */
        $row = $result->first();
        expect($row->isMismatch)->toBeTrue()
            ->and($row->timecardHours)->toBe(8.0)
            ->and($row->dailyHours)->toBe(10.0)
            ->and($row->variance)->toBe(2.0);
    });

    it('includes a row with only timecard hours and no daily report', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $date = '2025-04-07';

        TimecardEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'date' => $date,
            'hours' => 6.0,
        ]);

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse($date),
            Carbon::parse($date),
        );

        /** @var ReconciliationRow $row */
        $row = $result->first();
        expect($row->timecardHours)->toBe(6.0)
            ->and($row->dailyHours)->toBe(0.0)
            ->and($row->isMismatch)->toBeTrue();
    });

    it('includes a row with only daily report hours and no timecard entry', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $date = '2025-04-07';

        DailyReport::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'report_date' => $date,
            'total_hours' => 5.0,
        ]);

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse($date),
            Carbon::parse($date),
        );

        /** @var ReconciliationRow $row */
        $row = $result->first();
        expect($row->timecardHours)->toBe(0.0)
            ->and($row->dailyHours)->toBe(5.0)
            ->and($row->isMismatch)->toBeTrue();
    });
});

// ─── Multiple days ────────────────────────────────────────────────────────────

describe('multiple days', function (): void {
    it('returns one row per worked date', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        foreach (['2025-04-07', '2025-04-08', '2025-04-09'] as $date) {
            TimecardEntry::factory()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'date' => $date,
                'hours' => 8.0,
            ]);

            DailyReport::factory()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'report_date' => $date,
                'total_hours' => 8.0,
            ]);
        }

        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse('2025-04-07'),
            Carbon::parse('2025-04-09'),
        );

        expect($result)->toHaveCount(3);
        expect($result->every(fn (ReconciliationRow $r) => ! $r->isMismatch))->toBeTrue();
    });
});

// ─── Project filter ───────────────────────────────────────────────────────────

describe('project filter', function (): void {
    it('scopes results to the provided project id', function (): void {
        enableReconciliation();

        $user = User::factory()->create();
        $projectA = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $projectB = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $date = '2025-04-07';

        TimecardEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $projectA->id,
            'date' => $date,
            'hours' => 4.0,
        ]);

        TimecardEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $projectB->id,
            'date' => $date,
            'hours' => 4.0,
        ]);

        // Only reconcile against project A
        $result = reconciliationService()->reconcile(
            $user->id,
            Carbon::parse($date),
            Carbon::parse($date),
            $projectA->id,
        );

        expect($result)->toHaveCount(1);
        expect($result->first()->timecardHours)->toBe(4.0);
    });
});
