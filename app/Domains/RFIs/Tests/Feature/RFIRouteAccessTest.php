<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from admin rfi routes', function (): void {
    $rfi = RFI::factory()->create();

    get(route('admin.rfis.index'))->assertRedirect(route('login'));
    get(route('admin.rfis.show', $rfi))->assertRedirect(route('login'));
});

it('allows users with rfis.view-any to access admin rfi index', function (): void {
    $user = userWithRfiRoutePermissions(['rfis.view-any']);

    actingAs($user);

    get(route('admin.rfis.index'))
        ->assertSuccessful()
        ->assertSee('RFIs');
});

it('allows owners with rfis.view to access admin rfi show', function (): void {
    $user = userWithRfiRoutePermissions(['rfis.view']);

    $rfi = RFI::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'requested_by_id' => $user->id,
    ]);

    actingAs($user);

    get(route('admin.rfis.show', $rfi))->assertSuccessful();
});

it('forbids non-owners with rfis.view from admin rfi show', function (): void {
    $viewer = userWithRfiRoutePermissions(['rfis.view']);

    $rfi = RFI::factory()->create([
        'project_id' => Project::factory()->create()->id,
    ]);

    actingAs($viewer);

    get(route('admin.rfis.show', $rfi))->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithRfiRoutePermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'RFI Route Role '.str()->uuid(),
        'description' => 'Role for RFI route tests',
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
