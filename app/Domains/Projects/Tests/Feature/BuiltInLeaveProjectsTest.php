<?php

use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Projects\Database\Seeders\BuiltInLeaveProjectsSeeder;
use App\Domains\Projects\Models\Project;

it('seeds built-in sick and vacation projects', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $standardPayRateTypeId = PayRateType::query()
        ->where('key', 'standard')
        ->value('id');

    $this->assertDatabaseHas('projects', [
        'project_number' => Project::BUILT_IN_SICK_PROJECT_NUMBER,
        'is_active' => true,
        'pay_rate_type_id' => $standardPayRateTypeId,
    ]);

    $this->assertDatabaseHas('projects', [
        'project_number' => Project::BUILT_IN_VACATION_PROJECT_NUMBER,
        'is_active' => true,
        'pay_rate_type_id' => $standardPayRateTypeId,
    ]);
});

it('creates the standard pay rate type when built-in leave projects are seeded directly', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    expect(PayRateType::query()->where('key', 'standard')->exists())->toBeTrue();
});

it('seeding built-in leave projects is idempotent', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $count = Project::query()
        ->whereIn('project_number', [
            Project::BUILT_IN_SICK_PROJECT_NUMBER,
            Project::BUILT_IN_VACATION_PROJECT_NUMBER,
        ])
        ->count();

    expect($count)->toBe(2);
});

it('creates built-in leave projects and keeps pay rate type', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $count = Project::query()
        ->whereIn('project_number', [
            Project::BUILT_IN_SICK_PROJECT_NUMBER,
            Project::BUILT_IN_VACATION_PROJECT_NUMBER,
        ])
        ->count();

    expect($count)->toBe(2);
});
