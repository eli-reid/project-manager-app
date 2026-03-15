<?php

use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskTreeService;

it('rebuilds cached category tree when category is created', function (): void {
    $project = Project::factory()->create();
    $treeService = app(TaskTreeService::class);

    $initialTree = $treeService->getCachedCategoryTree($project->id);
    expect($initialTree)->toHaveCount(0);

    TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Electrical',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $refreshedTree = $treeService->getCachedCategoryTree($project->id);

    expect($refreshedTree)->toHaveCount(1)
        ->and($refreshedTree->first()?->name)->toBe('Electrical');
});

it('rebuilds cached category tree when category is deleted', function (): void {
    $project = Project::factory()->create();
    $category = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Plumbing',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $treeService = app(TaskTreeService::class);
    $initialTree = $treeService->getCachedCategoryTree($project->id);
    expect($initialTree)->toHaveCount(1);

    $category->delete();

    $refreshedTree = $treeService->getCachedCategoryTree($project->id);
    expect($refreshedTree)->toHaveCount(0);
});
