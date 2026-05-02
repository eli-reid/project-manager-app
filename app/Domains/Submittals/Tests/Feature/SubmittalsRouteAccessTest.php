<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from submittal routes', function (): void {
    $submittal = Submittal::factory()->create();

    get(route('submittals.index'))->assertRedirect(route('login'));
    get(route('submittals.create'))->assertRedirect(route('login'));
    get(route('submittals.show', $submittal))->assertRedirect(route('login'));
    get(route('submittals.mobile.index'))->assertRedirect(route('login'));
    get(route('admin.submittals.index'))->assertRedirect(route('login'));
});

it('forbids authenticated users without submittal permissions', function (): void {
    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $submittal = Submittal::factory()->create();

    actingAs($user);

    get(route('submittals.index'))->assertForbidden();
    get(route('submittals.create'))->assertForbidden();
    get(route('submittals.show', $submittal))->assertForbidden();
    get(route('admin.submittals.index'))->assertForbidden();
});

it('allows users with submittals.view-any to access indexes and admin queue', function (): void {
    $user = userWithSubmittalPermissions(['submittals.view-any']);

    actingAs($user);

    get(route('submittals.index'))
        ->assertSuccessful()
        ->assertSee('Submittals');

    get(route('submittals.mobile.index'))
        ->assertSuccessful()
        ->assertSee('Submittals');

    get(route('admin.submittals.index'))
        ->assertSuccessful()
        ->assertSee('Submittal Approval Queue');
});

it('allows owners with view/update/submit permissions to access own submittal pages', function (): void {
    $user = userWithSubmittalPermissions([
        'submittals.view',
        'submittals.update',
        'submittals.submit',
        'submittals.create',
    ]);

    $submittal = Submittal::factory()->create([
        'submitted_by_id' => $user->id,
    ]);

    actingAs($user);

    get(route('submittals.show', $submittal))->assertSuccessful();
    get(route('submittals.edit', $submittal))->assertSuccessful();
    get(route('submittals.create'))->assertSuccessful();
});

it('shows an upload document action when the selected project can manage project documents', function (): void {
    $user = userWithSubmittalPermissions([
        'submittals.create',
        'projects.view',
        'projects.view-any',
        'documents.view',
        'documents.manage-project',
    ]);

    $project = Project::factory()->create();

    actingAs($user);

    get(route('submittals.create', ['projectId' => (string) $project->id]))
        ->assertSuccessful()
        ->assertSee('Upload Document')
        ->assertSee(route('admin.projects.show', ['project' => $project, 'tab' => 'documents']), escape: false);
});

it('forbids non-owners with submittals.view from opening another user submittal', function (): void {
    $viewer = userWithSubmittalPermissions(['submittals.view']);

    $submittal = Submittal::factory()->create();

    actingAs($viewer);

    get(route('submittals.show', $submittal))->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithSubmittalPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Submittal Route Role '.str()->uuid(),
        'description' => 'Role for submittal route tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
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
