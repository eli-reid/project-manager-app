<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;

it('registers the financial reports route', function (): void {
    expect(route('reports.financial.index', absolute: false))->toBe('/reports/financial');
});

it('allows users with financial reports view permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertSuccessful()
        ->assertSee('Financial Reports');
});

it('allows admins to access financial reports route', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertSuccessful()
        ->assertSee('Project Profitability');
});

it('forbids users without financial reports view permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function reportsUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Reports Test Role '.str()->uuid(),
        'description' => 'Role created by reports tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            $permissionId = Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');

            return is_string($permissionId) ? $permissionId : null;
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
