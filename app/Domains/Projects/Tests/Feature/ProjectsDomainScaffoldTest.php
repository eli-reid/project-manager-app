<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Livewire\Admin\Projects\Form;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyWidget;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use Livewire\Livewire;

it('redirects guests from domain admin routes', function (): void {
    $this->get(route('admin.projects.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.clients.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.addresses.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without domain permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.projects.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.clients.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.addresses.index'))
        ->assertForbidden();
});

it('allows users with domain view permissions to access scaffold routes', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'clients.view',
        'addresses.view',
    ]);

    Project::factory()->create([
        'name' => 'City Center Renovation',
        'project_number' => 'PRJ-1001',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.index'))
        ->assertSuccessful()
        ->assertSee('Projects')
        ->assertSee('City Center Renovation');

    $this->actingAs($user)
        ->get(route('admin.clients.index'))
        ->assertSuccessful()
        ->assertSee('Clients');

    $this->actingAs($user)
        ->get(route('admin.addresses.index'))
        ->assertSuccessful()
        ->assertSee('Addresses');
});

it('shows inline client and address widgets on project create form', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'projects.create',
        'clients.create',
        'addresses.create',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.create'))
        ->assertSuccessful()
        ->assertSee('Quick Add Client')
        ->assertSee('Quick Add Address');
});

it('allows authorized users to edit and update a project', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'projects.edit',
    ]);

    $project = Project::factory()->create([
        'name' => 'Old Project Name',
        'project_number' => 'PRJ-EDIT-1',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.edit', $project))
        ->assertSuccessful()
        ->assertSee('Edit Project');

    $this->actingAs($user);

    Livewire::test(Form::class, ['project' => $project])
        ->set('name', 'Updated Project Name')
        ->set('project_number', 'PRJ-EDIT-1')
        ->set('status', 'in_progress')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->name)->toBe('Updated Project Name')
        ->and($project->fresh()->status?->value)->toBe('in_progress');
});

it('forbids users without edit permission from accessing project edit route', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.projects.edit', $project))
        ->assertForbidden();
});

it('shows the livewire tabbed project page and supports tab query state', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.create',
        'task-categories.view',
        'task-templates.view',
    ]);

    $project = Project::factory()->create([
        'name' => 'Tabbed Project View',
        'project_number' => 'PRJ-TABS-1',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Tabbed Project View')
        ->assertSee('Overview')
        ->assertSee('Tasks')
        ->assertDontSee('setTab(\'templates\')', false);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project).'?tab=tasks')
        ->assertSuccessful()
        ->assertSee('Project Work Breakdown')
        ->assertSee('Add Task')
        ->assertSee('Task Templates');
});

it('shows invoices tab on project view when user can view invoices', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'invoices.view',
    ]);

    $project = Project::factory()->create([
        'name' => 'Project With Invoices',
        'project_number' => 'PRJ-INV-1',
    ]);

    Invoice::factory()->for($project)->create([
        'vendor_name' => 'Vendor On Project',
    ]);

    Invoice::factory()->for(Project::factory())->create([
        'vendor_name' => 'Vendor Other Project',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Invoices');

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project).'?tab=invoices')
        ->assertSuccessful()
        ->assertSee('Project Invoices')
        ->assertSee('Vendor On Project')
        ->assertDontSee('Vendor Other Project');
});

it('shows dailies tab on project view when user can view all dailies', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'dailies.view-all',
    ]);

    $project = Project::factory()->create([
        'name' => 'Project With Dailies',
        'project_number' => 'PRJ-DLY-1',
    ]);

    DailyReport::factory()->for($project)->create([
        'status' => DailyReport::STATUS_SUBMITTED,
        'total_hours' => 8,
    ]);

    DailyReport::factory()->for(Project::factory())->create([
        'status' => DailyReport::STATUS_APPROVED,
        'total_hours' => 12,
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Dailies');

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project).'?tab=dailies')
        ->assertSuccessful()
        ->assertSee('Project Dailies')
        ->assertSee('Submitted')
        ->assertSee('8.00')
        ->assertDontSee('12.00');
});

it('auto generates project numbers with configured prefix when enabled', function (): void {
    Settings::set('projects.auto_generate_numbers', 'true');
    Settings::set('projects.number_prefix', 'JOB-');

    Project::factory()->create(['project_number' => 'JOB-0007']);
    Project::factory()->create(['project_number' => 'PRJ-9999']);

    $project = Project::factory()->create(['project_number' => null]);

    expect($project->project_number)->toBe('JOB-0008');
});

it('does not auto generate project numbers when disabled', function (): void {
    Settings::set('projects.auto_generate_numbers', 'false');
    Settings::set('projects.number_prefix', 'JOB-');

    $project = Project::factory()->create(['project_number' => null]);

    expect($project->project_number)->toBeNull();
});

it('keeps manually entered project number when auto generation is enabled', function (): void {
    Settings::set('projects.auto_generate_numbers', 'true');
    Settings::set('projects.number_prefix', 'JOB-');

    $project = Project::factory()->create(['project_number' => 'CUSTOM-42']);

    expect($project->project_number)->toBe('CUSTOM-42');
});

it('copies category tasks from project show actions menu flow', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.create',
        'task-categories.view',
    ]);

    $project = Project::factory()->create();
    $sourceCategory = TaskCategory::factory()->create(['project_id' => $project->id, 'name' => 'Electrical']);
    $targetCategory = TaskCategory::factory()->create(['project_id' => $project->id, 'name' => 'Framing']);

    $parentTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $sourceCategory->id,
        'parent_task_id' => null,
        'title' => 'Install conduit',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $sourceCategory->id,
        'parent_task_id' => $parentTask->id,
        'title' => 'Conduit supports',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('copySourceCategoryId', $sourceCategory->id)
        ->set('copyTargetCategoryId', $targetCategory->id)
        ->set('copyIncludeSubtasks', true)
        ->call('copyCategoryTasks')
        ->assertHasNoErrors();

    $copiedParent = Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $targetCategory->id)
        ->whereNull('parent_task_id')
        ->where('title', 'Install conduit (Copy)')
        ->first();

    expect($copiedParent)->not->toBeNull();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $targetCategory->id)
        ->where('parent_task_id', $copiedParent?->id)
        ->where('title', 'Conduit supports (Copy)')
        ->exists())->toBeTrue();
});

it('creates category and task from inline forms on project show', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.create',
        'task-categories.view',
        'task-categories.create',
    ]);

    $project = Project::factory()->create();
    $existingCategory = TaskCategory::factory()->create(['project_id' => $project->id, 'name' => 'Foundation']);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('inlineCategoryName', 'Electrical')
        ->set('inlineCategoryParentId', $existingCategory->id)
        ->set('inlineCategoryDescription', 'Power distribution')
        ->call('createInlineCategory')
        ->assertHasNoErrors();

    $createdCategory = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Electrical')
        ->first();

    expect($createdCategory)->not->toBeNull();

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('inlineTaskTitle', 'Install panel')
        ->set('inlineTaskDescription', 'Main service panel setup')
        ->set('inlineTaskCategoryId', $createdCategory?->id)
        ->set('inlineTaskAssignedTo', null)
        ->call('createInlineTask')
        ->assertHasNoErrors();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $createdCategory?->id)
        ->where('title', 'Install panel')
        ->exists())->toBeTrue();
});

it('deletes task from project show when user has permission', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.delete',
    ]);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'parent_task_id' => null,
        'title' => 'Delete Me',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('deleteTask', $task->id)
        ->assertHasNoErrors();

    expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
});

it('does not delete task that has subtasks', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.delete',
    ]);

    $project = Project::factory()->create();
    $parentTask = Task::factory()->create([
        'project_id' => $project->id,
        'parent_task_id' => null,
        'title' => 'Parent Task',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'parent_task_id' => $parentTask->id,
        'title' => 'Child Task',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('deleteTask', $parentTask->id)
        ->assertHasNoErrors();

    expect(Task::query()->whereKey($parentTask->id)->exists())->toBeTrue();
});

it('copies a category from project show actions', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.create',
    ]);

    $project = Project::factory()->create();
    $parent = TaskCategory::factory()->create(['project_id' => $project->id, 'name' => 'Phase 1']);
    $source = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $parent->id,
        'name' => 'Electrical',
        'description' => 'Original category',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('copyCategorySourceId', $source->id)
        ->call('copyCategory')
        ->assertHasNoErrors();

    $copied = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Electrical (Copy)')
        ->first();

    expect($copied)->not->toBeNull();
    expect($copied?->parent_id)->toBe($parent->id);
    expect($copied?->description)->toBe('Original category');
});

it('deletes an empty category from project show when user has permission', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.delete',
    ]);

    $project = Project::factory()->create();
    $category = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Temporary Category',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('deleteCategory', $category->id)
        ->assertHasNoErrors();

    expect(TaskCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('deletes category branch including descendant categories and tasks', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.delete',
    ]);

    $project = Project::factory()->create();
    $root = TaskCategory::factory()->create(['project_id' => $project->id]);
    $branchCategory = TaskCategory::factory()->create(['project_id' => $project->id, 'parent_id' => $root->id]);
    $descendant = TaskCategory::factory()->create(['project_id' => $project->id, 'parent_id' => $branchCategory->id]);
    $sibling = TaskCategory::factory()->create(['project_id' => $project->id, 'parent_id' => $root->id]);

    $branchTask = Task::factory()->create(['project_id' => $project->id, 'task_category_id' => $branchCategory->id]);
    $descendantTask = Task::factory()->create(['project_id' => $project->id, 'task_category_id' => $descendant->id]);
    $siblingTask = Task::factory()->create(['project_id' => $project->id, 'task_category_id' => $sibling->id]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('deleteCategory', $branchCategory->id)
        ->assertHasNoErrors();

    expect(TaskCategory::query()->whereKey($branchCategory->id)->exists())->toBeFalse();
    expect(TaskCategory::query()->whereKey($descendant->id)->exists())->toBeFalse();
    expect(TaskCategory::query()->whereKey($sibling->id)->exists())->toBeTrue();

    expect(Task::query()->whereKey($branchTask->id)->exists())->toBeFalse();
    expect(Task::query()->whereKey($descendantTask->id)->exists())->toBeFalse();
    expect(Task::query()->whereKey($siblingTask->id)->exists())->toBeTrue();
});

it('gracefully handles deleting a stale category id from project show', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.delete',
    ]);

    $project = Project::factory()->create();
    $category = TaskCategory::factory()->create(['project_id' => $project->id]);
    $staleId = $category->id;
    $category->delete();

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('deleteCategory', $staleId)
        ->assertHasNoErrors();
});

it('copies a task from project show task row action', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'tasks.view',
        'tasks.create',
    ]);

    $project = Project::factory()->create();
    $category = TaskCategory::factory()->create(['project_id' => $project->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'title' => 'Main Task',
        'parent_task_id' => null,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $category->id,
        'title' => 'Sub Task',
        'parent_task_id' => $task->id,
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('copyTaskFrom', $task->id)
        ->assertHasNoErrors();

    $copiedTask = Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $category->id)
        ->whereNull('parent_task_id')
        ->where('title', 'Main Task (Copy)')
        ->first();

    expect($copiedTask)->not->toBeNull();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('parent_task_id', $copiedTask?->id)
        ->where('title', 'Sub Task (Copy)')
        ->exists())->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithProjectDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Domain Scaffold Role '.str()->uuid(),
        'description' => 'Role for domain scaffold feature tests',
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
