<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Livewire\Admin\Projects\Form;
use App\Domains\Projects\Livewire\User\Projects\Index as UserProjectsIndex;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyWidget;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use Livewire\Livewire;

it('redirects guests from domain admin routes', function (): void {
    $this->get(route('admin.projects.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.clients.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.addresses.index'))
        ->assertRedirect(route('login'));
});

it('redirects guests from user project routes', function (): void {
    $project = Project::factory()->create();

    $this->get(route('projects.index'))
        ->assertRedirect(route('login'));

    $this->get(route('projects.show', $project))
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

it('forbids authenticated users without project view permission from user project routes', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

it('shows only assigned active and open projects by default on user project list', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    $visibleProject = Project::factory()->create([
        'name' => 'Open Job Visible',
        'project_manager_id' => $user->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $unassignedOpenProject = Project::factory()->create([
        'name' => 'Open Job Unassigned Hidden',
        'project_manager_id' => null,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $closedProject = Project::factory()->create([
        'name' => 'Closed Job Hidden',
        'status' => 'completed',
        'is_active' => true,
    ]);

    $inactiveProject = Project::factory()->create([
        'name' => 'Inactive Job Hidden',
        'status' => 'active',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertSuccessful()
        ->assertSee('Open Job Visible')
        ->assertDontSee('Open Job Unassigned Hidden')
        ->assertDontSee('Closed Job Hidden')
        ->assertDontSee('Inactive Job Hidden');

    $this->actingAs($user)
        ->get(route('projects.show', $visibleProject))
        ->assertSuccessful()
        ->assertSee('Open Job Visible');

    expect($closedProject->exists)->toBeTrue()
        ->and($inactiveProject->exists)->toBeTrue()
        ->and($unassignedOpenProject->exists)->toBeTrue();
});

it('does not offer include closed filter on user project list', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    Project::factory()->create([
        'name' => 'Only Active Listing Project',
        'project_manager_id' => $user->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertSuccessful()
        ->assertSee('Only Active Listing Project')
        ->assertDontSee('Include closed projects');
});

it('does not render client company name on the user project list', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);
    $client = Client::factory()->create([
        'company_name' => 'Acme Civil Group',
    ]);

    Project::factory()->create([
        'name' => 'Client Linked Project',
        'project_manager_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertSuccessful()
        ->assertSee('Client Linked Project')
        ->assertDontSee('Acme Civil Group');
});

it('shows project address details on user project list', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    $client = Client::factory()->create([
        'company_name' => 'Address Client LLC',
    ]);

    $address = Address::factory()->create([
        'client_id' => $client->id,
        'address1' => '123 Field St',
        'city' => 'Riverside',
        'state' => 'CA',
        'zip' => '92501',
    ]);

    Project::factory()->create([
        'name' => 'Address Visible Project',
        'project_manager_id' => $user->id,
        'client_id' => $client->id,
        'address_id' => $address->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertSuccessful()
        ->assertSee('Address Visible Project')
        ->assertSee('123 Field St')
        ->assertSee('Riverside, CA, 92501')
        ->assertSee('Open in Maps');
});

it('allows searching user projects by address fields', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    $searchableClient = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $searchableAddress = Address::factory()->create([
        'client_id' => $searchableClient->id,
        'city' => 'Searchville',
    ]);

    $otherAddress = Address::factory()->create([
        'client_id' => $otherClient->id,
        'city' => 'Othercity',
    ]);

    Project::factory()->create([
        'name' => 'Project Matching Address Search',
        'project_manager_id' => $user->id,
        'client_id' => $searchableClient->id,
        'address_id' => $searchableAddress->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    Project::factory()->create([
        'name' => 'Project Not Matching Address Search',
        'project_manager_id' => $user->id,
        'client_id' => $otherClient->id,
        'address_id' => $otherAddress->id,
        'status' => 'in_progress',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(UserProjectsIndex::class)
        ->set('search', 'Searchville')
        ->assertSee('Project Matching Address Search')
        ->assertDontSee('Project Not Matching Address Search');
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

it('renders project index actions with row navigation exclusion markers', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
    ]);

    Project::factory()->create([
        'name' => 'Navigation Marker Project',
        'project_number' => 'PRJ-NAV-1',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.index'))
        ->assertSuccessful()
        ->assertSee('data-prevent-row-nav', false)
        ->assertSee('window.Livewire?.navigate', false);
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
        ->assertSee('Leave Tracking')
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
        ->set('leave_category', 'vacation')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->name)->toBe('Updated Project Name')
        ->and($project->fresh()->status?->value)->toBe('in_progress')
        ->and($project->fresh()->leave_category)->toBe('vacation');
});

it('shows unassigned and selected addresses on project edit form', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'projects.edit',
    ]);

    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    $unassignedAddress = Address::factory()->create([
        'client_id' => null,
        'address1' => 'Unassigned Job Address',
    ]);

    $selectedAddressFromDifferentClient = Address::factory()->create([
        'client_id' => $clientB->id,
        'address1' => 'Selected Different Client Address',
    ]);

    $project = Project::factory()->create([
        'client_id' => $clientA->id,
        'address_id' => $selectedAddressFromDifferentClient->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['project' => $project])
        ->assertSee('Unassigned Job Address')
        ->assertSee('Selected Different Client Address');

    expect($project->fresh()->address_id)->toBe($selectedAddressFromDifferentClient->id)
        ->and($unassignedAddress->exists)->toBeTrue();
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
        ->assertDispatched('project-tasks-updated')
        ->assertHasNoErrors();

    $copied = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Electrical')
        ->where('id', '!=', $source->id)
        ->first();

    expect($copied)->not->toBeNull();
    expect($copied?->parent_id)->toBe($parent->id);
    expect($copied?->description)->toBe('Original category');
});

it('copies category descendants and tasks without renaming them', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.create',
        'tasks.view',
        'tasks.create',
    ]);

    $project = Project::factory()->create();
    $source = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Electrical',
    ]);
    $child = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $source->id,
        'name' => 'Rough-In',
    ]);

    $sourceTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $source->id,
        'parent_task_id' => null,
        'title' => 'Run conduit',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $source->id,
        'parent_task_id' => $sourceTask->id,
        'title' => 'Inspect conduit',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $child->id,
        'parent_task_id' => null,
        'title' => 'Install boxes',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('copyCategorySourceId', $source->id)
        ->set('copyIncludeChildCategories', true)
        ->set('copyIncludeCategoryTasks', true)
        ->call('copyCategory')
        ->assertHasNoErrors();

    $copiedSource = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Electrical')
        ->where('id', '!=', $source->id)
        ->first();

    expect($copiedSource)->not->toBeNull();

    $copiedChild = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Rough-In')
        ->where('parent_id', $copiedSource?->id)
        ->first();

    expect($copiedChild)->not->toBeNull();

    $copiedParentTask = Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $copiedSource?->id)
        ->whereNull('parent_task_id')
        ->where('title', 'Run conduit')
        ->first();

    expect($copiedParentTask)->not->toBeNull();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $copiedSource?->id)
        ->where('parent_task_id', $copiedParentTask?->id)
        ->where('title', 'Inspect conduit')
        ->exists())->toBeTrue();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $copiedChild?->id)
        ->where('title', 'Install boxes')
        ->exists())->toBeTrue();
});

it('renders nested category copy options as breadcrumbs', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.create',
    ]);

    $project = Project::factory()->create();
    $parent = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Building A',
    ]);
    $child = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $parent->id,
        'name' => 'Level 2',
    ]);
    $grandchild = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $child->id,
        'name' => 'Unit 201',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->assertSee($parent->name)
        ->assertSee('Building A -> Level 2')
        ->assertSee('Building A -> Level 2 -> Unit 201');
});

it('copies a category multiple times with unit-style names', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'task-categories.create',
        'tasks.view',
        'tasks.create',
    ]);

    $project = Project::factory()->create();
    $source = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Template Unit',
    ]);
    $sourceChild = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $source->id,
        'name' => 'Punch List',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $source->id,
        'parent_task_id' => null,
        'title' => 'Install cabinets',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->set('copyCategorySourceId', $source->id)
        ->set('copyIncludeChildCategories', true)
        ->set('copyIncludeCategoryTasks', true)
        ->set('copyCategoryQuantity', 2)
        ->set('copyCategoryNamePrefix', 'Unit')
        ->set('copyCategoryStartNumber', 201)
        ->call('copyCategory')
        ->assertHasNoErrors();

    $unit201 = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Unit 201')
        ->latest('created_at')
        ->first();

    $unit202 = TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('name', 'Unit 202')
        ->latest('created_at')
        ->first();

    expect($unit201)->not->toBeNull();
    expect($unit202)->not->toBeNull();

    expect(TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('parent_id', $unit201?->id)
        ->where('name', $sourceChild->name)
        ->exists())->toBeTrue();

    expect(TaskCategory::query()
        ->where('project_id', $project->id)
        ->where('parent_id', $unit202?->id)
        ->where('name', $sourceChild->name)
        ->exists())->toBeTrue();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $unit201?->id)
        ->where('title', 'Install cabinets')
        ->exists())->toBeTrue();

    expect(Task::query()
        ->where('project_id', $project->id)
        ->where('task_category_id', $unit202?->id)
        ->where('title', 'Install cabinets')
        ->exists())->toBeTrue();
});

it('saves a category branch as a task template', function (): void {
    $user = userWithProjectDomainPermissions([
        'projects.view',
        'task-categories.view',
        'tasks.view',
        'task-templates.create',
    ]);

    $project = Project::factory()->create();
    $source = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'name' => 'Apartment Unit Scope',
    ]);
    $child = TaskCategory::factory()->create([
        'project_id' => $project->id,
        'parent_id' => $source->id,
        'name' => 'Punch',
    ]);

    $parentTask = Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $source->id,
        'parent_task_id' => null,
        'title' => 'Install flooring',
        'priority' => Task::PRIORITY_HIGH,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $source->id,
        'parent_task_id' => $parentTask->id,
        'title' => 'Cleanup flooring debris',
        'priority' => Task::PRIORITY_MEDIUM,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'task_category_id' => $child->id,
        'parent_task_id' => null,
        'title' => 'Final paint touch-up',
        'priority' => Task::PRIORITY_LOW,
    ]);

    $this->actingAs($user);

    Livewire::test(TaskHierarchyWidget::class, ['project' => $project])
        ->call('startSaveCategoryAsTemplate', $source->id)
        ->set('saveTemplateName', 'Unit Turnover Template')
        ->call('saveCategoryAsTemplate')
        ->assertHasNoErrors();

    $template = TaskTemplate::query()
        ->where('name', 'Unit Turnover Template')
        ->latest('created_at')
        ->first();

    expect($template)->not->toBeNull();
    expect($template?->task_category_id)->toBe($source->id);

    $templateTaskTitles = collect($template?->template_tasks ?? [])->pluck('title')->all();
    expect($templateTaskTitles)->toContain('Install flooring');
    expect($templateTaskTitles)->toContain('Cleanup flooring debris');
    expect($templateTaskTitles)->toContain('Final paint touch-up');
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
        ->assertDispatched('project-tasks-updated')
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
        ->assertSet('copyTaskSourceId', $task->id)
        ->assertDispatched('open-copy-task-modal')
        ->call('copyTask')
        ->assertDispatched('project-tasks-updated')
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
