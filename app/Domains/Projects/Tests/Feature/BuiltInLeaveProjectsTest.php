<?php

use App\Domains\Projects\Database\Seeders\BuiltInLeaveProjectsSeeder;
use App\Domains\Projects\Models\Project;

it('seeds built-in sick and vacation projects', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $this->assertDatabaseHas('projects', [
        'project_number' => Project::BUILT_IN_SICK_PROJECT_NUMBER,
        'leave_category' => 'sick',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('projects', [
        'project_number' => Project::BUILT_IN_VACATION_PROJECT_NUMBER,
        'leave_category' => 'vacation',
        'is_active' => true,
    ]);
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

it('prevents deleting built-in leave projects', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $project = Project::query()
        ->where('project_number', Project::BUILT_IN_SICK_PROJECT_NUMBER)
        ->firstOrFail();

    expect(fn () => $project->delete())->toThrow(DomainException::class);
});

it('prevents changing leave category for built-in leave projects', function (): void {
    $this->seed(BuiltInLeaveProjectsSeeder::class);

    $project = Project::query()
        ->where('project_number', Project::BUILT_IN_VACATION_PROJECT_NUMBER)
        ->firstOrFail();

    expect(fn () => $project->update(['leave_category' => null]))->toThrow(DomainException::class);
});
