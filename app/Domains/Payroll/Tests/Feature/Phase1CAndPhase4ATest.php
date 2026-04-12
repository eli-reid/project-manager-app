<?php

use App\Domains\Payroll\Database\Seeders\PayRateTypeSeeder;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Services\PayRateIntegrityService;

// ─── Phase 1C: Active Rate Uniqueness Constraint ──────────────────────────────

it('allows creating an active rate when no conflict exists', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();

    $rate = PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
    ]);

    expect($rate->exists)->toBeTrue();
});

it('blocks creating a second active rate for the same employee, type, and project scope', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
        'effective_date' => now()->subMonth(),
    ]);

    expect(fn () => PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
        'effective_date' => now(),
    ]))->toThrow(DomainException::class);
});

it('allows a new active rate once the previous one is expired', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => now()->subDay(),
    ]);

    $newRate = PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
        'effective_date' => now(),
    ]);

    expect($newRate->exists)->toBeTrue();
});

it('treats default rate (null project) and project-specific rate as separate scopes', function (): void {
    $type = PayRateType::factory()->prevailingBase()->create();
    $profile = PayrollEmployeeProfile::factory()->create();
    $project = App\Domains\Projects\Models\Project::factory()->create(['is_prevailing_wage' => true]);

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
    ]);

    $projectRate = PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => $project->id,
        'expiration_date' => null,
    ]);

    expect($projectRate->exists)->toBeTrue();
});

it('allows different employees to have active rates for the same type and project', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $profileA = PayrollEmployeeProfile::factory()->create();
    $profileB = PayrollEmployeeProfile::factory()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profileA->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
    ]);

    $rateB = PayRate::factory()->create([
        'payroll_employee_profile_id' => $profileB->id,
        'pay_rate_type_id' => $type->id,
        'project_id' => null,
        'expiration_date' => null,
    ]);

    expect($rateB->exists)->toBeTrue();
});

it('returns only active rates via the active scope', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $profile = PayrollEmployeeProfile::factory()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'expiration_date' => now()->subDay(),
    ]);
    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $type->id,
        'expiration_date' => null,
        'effective_date' => now(),
    ]);

    $activeCount = PayRate::query()
        ->where('payroll_employee_profile_id', $profile->id)
        ->active()
        ->count();

    expect($activeCount)->toBe(1);
});

// ─── Phase 4A: System PayRateType Seeder ─────────────────────────────────────

it('seeds standard, prevailing_base, and prevailing_fringe rate types', function (): void {
    (new PayRateTypeSeeder)->run();

    expect(PayRateType::query()->where('key', 'standard')->exists())->toBeTrue()
        ->and(PayRateType::query()->where('key', 'prevailing_base')->exists())->toBeTrue()
        ->and(PayRateType::query()->where('key', 'prevailing_fringe')->exists())->toBeTrue();
});

it('marks all seeded system types as is_system = true', function (): void {
    (new PayRateTypeSeeder)->run();

    $nonSystem = PayRateType::query()
        ->whereIn('key', ['standard', 'prevailing_base', 'prevailing_fringe'])
        ->where('is_system', false)
        ->count();

    expect($nonSystem)->toBe(0);
});

it('is idempotent — re-running the seeder does not create duplicates', function (): void {
    (new PayRateTypeSeeder)->run();
    (new PayRateTypeSeeder)->run();

    $count = PayRateType::query()
        ->whereIn('key', ['standard', 'prevailing_base', 'prevailing_fringe'])
        ->count();

    expect($count)->toBe(3);
});

it('prevents deleting a system pay rate type', function (): void {
    $type = PayRateType::factory()->standard()->create();

    expect(fn () => $type->delete())->toThrow(DomainException::class);
});

it('prevents changing the key of a system pay rate type', function (): void {
    $type = PayRateType::factory()->standard()->create();

    expect(fn () => $type->update(['key' => 'changed']))->toThrow(DomainException::class);
});

it('allows updating non-key fields on a system pay rate type', function (): void {
    $type = PayRateType::factory()->standard()->create();
    $type->update(['description' => 'Updated description.']);

    expect($type->fresh()->description)->toBe('Updated description.');
});

it('allows deleting a non-system pay rate type', function (): void {
    $type = PayRateType::factory()->create(['is_system' => false]);
    $type->delete();

    expect(PayRateType::query()->find($type->id))->toBeNull();
});
