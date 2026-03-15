<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Domains\Projects\Livewire\Admin\Projects\Form;
use App\Domains\Projects\Models\Project;
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
