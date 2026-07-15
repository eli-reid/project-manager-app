<?php

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskHierarchyBulkActionService;

it('copies selected categories and standalone tasks without duplicating covered descendants', function (): void {
    $project = Project::factory()->create();

    $category = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Electrical',
    ]);

    $branchTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'title' => 'Panel 01',
    ]);

    $branchSubTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'parent_task_id' => $branchTask->id,
        'title' => 'Panel 01 - Finish',
    ]);

    $standaloneCategory = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Closeout',
    ]);

    $standaloneTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $standaloneCategory->id,
        'title' => 'Checklist',
    ]);

    $result = app(TaskHierarchyBulkActionService::class)->copySelected(
        $project,
        [$branchSubTask->id, $standaloneTask->id],
        [$category->id],
    );

    expect($result)->toBe([
        'copiedTasks' => 3,
        'copiedCategories' => 1,
    ]);

    expect(TaskCategory::query()->where('project_id', $project->id)->pluck('name')->all())
        ->toContain('Electrical (Copy)');

    expect(Task::query()->where('project_id', $project->id)->pluck('title')->all())
        ->toContain('Panel 01 (Copy)')
        ->toContain('Panel 01 - Finish (Copy)')
        ->toContain('Checklist (Copy)');
});

it('deletes selected categories and tasks while skipping tasks with remaining subtasks', function (): void {
    $project = Project::factory()->create();

    $category = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Rough In',
    ]);

    $categoryTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
    ]);

    $parentCategory = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Finish',
    ]);

    $parentTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $parentCategory->id,
        'title' => 'Final Walkthrough',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $parentCategory->id,
        'parent_task_id' => $parentTask->id,
        'title' => 'Punch Follow-up',
    ]);

    $leafTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $parentCategory->id,
        'title' => 'Owner Signoff',
    ]);

    $result = app(TaskHierarchyBulkActionService::class)->deleteSelected(
        $project,
        [$parentTask->id, $leafTask->id],
        [$category->id],
    );

    expect($result)->toBe([
        'deletedTasks' => 1,
        'deletedCategories' => 1,
        'skippedTasks' => 1,
    ]);

    expect(TaskCategory::query()->whereKey($category->id)->exists())->toBeFalse();
    expect(Task::query()->whereKey($categoryTask->id)->exists())->toBeFalse();
    expect(Task::query()->whereKey($leafTask->id)->exists())->toBeFalse();
    expect(Task::query()->whereKey($parentTask->id)->exists())->toBeTrue();
});

it('marks selected tasks complete', function (): void {
    $project = Project::factory()->create();
    $category = TaskCategory::factory()->create(['project_id' => $project->id]);

    $taskA = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'status' => Task::STATUS_TODO,
        'completion_percentage' => 0,
    ]);

    $taskB = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'status' => Task::STATUS_IN_PROGRESS,
        'completion_percentage' => 50,
    ]);

    $updatedCount = app(TaskHierarchyBulkActionService::class)->markTasksComplete($project, [$taskA->id, $taskB->id]);

    expect($updatedCount)->toBe(2);

    expect($taskA->fresh()->status)->toBe(Task::STATUS_COMPLETED);
    expect($taskA->fresh()->completion_percentage)->toBe(100);
    expect($taskB->fresh()->status)->toBe(Task::STATUS_COMPLETED);
    expect($taskB->fresh()->completion_percentage)->toBe(100);
});
