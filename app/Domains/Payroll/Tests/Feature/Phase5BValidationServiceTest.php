<?php

use App\Domains\Payroll\Data\ValidationResult;
use App\Domains\Payroll\Data\ValidationViolation;
use App\Domains\Payroll\Enums\ValidationSeverity;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Services\PayPeriodService;
use App\Domains\Payroll\Services\PayrollRateResolutionService;
use App\Domains\Payroll\Services\TimecardEntryValidationService;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;

function makeValidationService(): TimecardEntryValidationService
{
    return new TimecardEntryValidationService(
        new PayPeriodService,
        new PayrollRateResolutionService,
    );
}

// ─── V-01: Active payroll profile ────────────────────────────────────────────

describe('V-01 – active payroll profile', function (): void {
    it('passes when the employee has an active profile', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-01');
    });

    it('blocks when the employee has no payroll profile', function (): void {
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $entry = TimecardEntry::factory()->create([
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-01');
    });

    it('blocks when the employee profile status is not active', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'terminated']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-01');
    });
});

// ─── V-02: Active project ─────────────────────────────────────────────────────

describe('V-02 – active project', function (): void {
    it('passes for an in-progress project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-02');
    });

    it('blocks for a completed project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::COMPLETED]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-02');
    });

    it('blocks for a cancelled project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::CANCELLED]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-02');
    });
});

// ─── V-03: Cost code belongs to project ──────────────────────────────────────

describe('V-03 – cost code belongs to project', function (): void {
    it('passes when cost code belongs to the entry project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $costCode = CostCode::factory()->create(['project_id' => $project->id]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'cost_code_id' => $costCode->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-03');
    });

    it('blocks when cost code belongs to a different project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $otherProject = Project::factory()->create();
        $costCode = CostCode::factory()->create(['project_id' => $otherProject->id]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'cost_code_id' => $costCode->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-03');
    });
});

// ─── V-04: Work date not in future ───────────────────────────────────────────

describe('V-04 – work date not in future', function (): void {
    it('passes for today', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-04');
    });

    it('blocks for a future date', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->addDays(3)->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-04');
    });
});

// ─── V-05: Within current or prior pay period ────────────────────────────────

describe('V-05 – within current or prior pay period', function (): void {
    it('passes for today', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-05');
    });

    it('blocks for a date older than two pay periods', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->subWeeks(3)->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-05');
    });
});

// ─── V-06 / V-07: Daily hour totals ──────────────────────────────────────────

describe('V-06 & V-07 – daily hour limits', function (): void {
    it('produces no daily-hours violation under 16 hours', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $ruleIds = collect($result->violations)->pluck('ruleId')->toArray();

        expect($ruleIds)->not->toContain('V-06')
            ->and($ruleIds)->not->toContain('V-07');
    });

    it('produces a warning (V-07) when daily total exceeds 16 hours', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $today = now()->toDateString();

        // Existing entry uses same user+date
        TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => $today,
            'hours' => 12,
        ]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => $today,
            'hours' => 6,
        ]);

        $result = makeValidationService()->validate($entry);
        $warnings = collect($result->warnings())->pluck('ruleId')->toArray();

        expect($warnings)->toContain('V-07');
    });

    it('produces a block (V-06) when daily total exceeds 24 hours', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $today = now()->toDateString();

        TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => $today,
            'hours' => 20,
        ]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => $today,
            'hours' => 6,
        ]);

        $result = makeValidationService()->validate($entry);
        $blocks = collect($result->blocks())->pluck('ruleId')->toArray();

        expect($blocks)->toContain('V-06');
    });
});

// ─── V-08: Duplicate detection ───────────────────────────────────────────────

describe('V-08 – duplicate entry', function (): void {
    it('warns when an identical entry already exists', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $today = now()->toDateString();

        $first = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'cost_code_id' => null,
            'date' => $today,
            'hours' => 4,
        ]);

        $second = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'cost_code_id' => null,
            'date' => $today,
            'hours' => 4,
        ]);

        $result = makeValidationService()->validate($second);
        $warnings = collect($result->warnings())->pluck('ruleId')->toArray();

        expect($warnings)->toContain('V-08');
        unset($first); // suppress unused var
    });

    it('does not warn when entries differ by project', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project1 = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $project2 = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        $today = now()->toDateString();

        TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project1->id,
            'cost_code_id' => null,
            'date' => $today,
            'hours' => 4,
        ]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project2->id,
            'cost_code_id' => null,
            'date' => $today,
            'hours' => 4,
        ]);

        $result = makeValidationService()->validate($entry);
        $warnings = collect($result->warnings())->pluck('ruleId')->toArray();

        expect($warnings)->not->toContain('V-08');
    });
});

// ─── V-09: No approved rate ───────────────────────────────────────────────────

describe('V-09 – no approved rate', function (): void {
    it('warns when employee has no active standard rate', function (): void {
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);
        // Ensure 'standard' type exists but no rate record for this employee
        PayRateType::factory()->standard()->create();

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $warnings = collect($result->warnings())->pluck('ruleId')->toArray();

        expect($warnings)->toContain('V-09');
    });

    it('does not warn when employee has an active standard rate', function (): void {
        $type = PayRateType::factory()->standard()->create();
        $profile = PayrollEmployeeProfile::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['status' => ProjectStatusEnum::IN_PROGRESS]);

        PayRate::factory()->create([
            'payroll_employee_profile_id' => $profile->id,
            'pay_rate_type_id' => $type->id,
            'project_id' => null,
            'effective_date' => now()->subMonth()->toDateString(),
            'expiration_date' => null,
        ]);

        $entry = TimecardEntry::factory()->create([
            'user_id' => $profile->user_id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 8,
        ]);

        $result = makeValidationService()->validate($entry);
        $warnings = collect($result->warnings())->pluck('ruleId')->toArray();

        expect($warnings)->not->toContain('V-09');
    });
});

// ─── ValidationResult helpers ─────────────────────────────────────────────────

describe('ValidationResult', function (): void {
    it('passes() returns true when there are no violations', function (): void {
        expect((new ValidationResult)->passes())->toBeTrue();
    });

    it('passes() returns true when violations are all warnings', function (): void {
        $result = new ValidationResult([
            new ValidationViolation('V-07', ValidationSeverity::Warning, 'test'),
        ]);

        expect($result->passes())->toBeTrue();
    });

    it('passes() returns false when a block violation exists', function (): void {
        $result = new ValidationResult([
            new ValidationViolation('V-01', ValidationSeverity::Block, 'test'),
        ]);

        expect($result->passes())->toBeFalse();
    });
});
