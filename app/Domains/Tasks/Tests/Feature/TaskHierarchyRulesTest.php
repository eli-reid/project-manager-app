<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Livewire\Admin\TaskCategories\Form as TaskCategoryForm;
use App\Domains\Tasks\Livewire\Admin\Tasks\Form as TaskForm;
use App\Domains\Tasks\Livewire\Admin\TaskTemplates\Form as TaskTemplateForm;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use Livewire\Livewire;

it('requires a project for task categories', function (): void {
    $user = userWithTaskDomainPermissions([
        'task-categories.create',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskCategoryForm::class)
        ->set('name', 'Electrical')
        ->set('project_id', null)
        ->call('save')
        ->assertHasErrors(['project_id']);
});

it('requires task category to belong to selected project', function (): void {
    $user = userWithTaskDomainPermissions([
        'tasks.create',
    ]);

    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    $categoryForProjectB = TaskCategory::factory()->create(['project_id' => $projectB->id]);

    $this->actingAs($user);

    Livewire::test(TaskForm::class)
        ->set('title', 'Install conduit')
        ->set('project_id', $projectA->id)
        ->set('task_category_id', $categoryForProjectB->id)
        ->call('save')
        ->assertHasErrors(['task_category_id']);
});

it('requires parent task to belong to selected project', function (): void {
    $user = userWithTaskDomainPermissions([
        'tasks.create',
    ]);

    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $categoryForProjectA = TaskCategory::factory()->create(['project_id' => $projectA->id]);
    $parentTaskInProjectB = Task::factory()->create(['project_id' => $projectB->id]);

    $this->actingAs($user);

    Livewire::test(TaskForm::class)
        ->set('title', 'Wire panel')
        ->set('project_id', $projectA->id)
        ->set('task_category_id', $categoryForProjectA->id)
        ->set('parent_task_id', $parentTaskInProjectB->id)
        ->call('save')
        ->assertHasErrors(['parent_task_id']);
});

it('allows global task templates to be created without project context', function (): void {
    $user = userWithTaskDomainPermissions([
        'task-templates.create',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskTemplateForm::class)
        ->set('name', 'Standard Rough-In')
        ->set('task_category_id', null)
        ->set('priority', Task::PRIORITY_MEDIUM)
        ->set('template_tasks', [])
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        TaskTemplate::query()->where('name', 'Standard Rough-In')->exists()
    )->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithTaskDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Task Domain Role '.str()->uuid(),
        'description' => 'Role for task domain feature tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
