<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Permission\Services\PermissionRegistry;
use App\Core\Auth\Role\Models\Role;

it('preserves built-in user custom permissions while adding new built-in defaults', function (): void {
    $initialRegistry = new PermissionRegistry;
    $initialRegistry->registerPermissions([
        ['resource' => 'reports', 'action' => 'view', 'built_in_roles' => [Role::BUILT_IN_USER]],
        ['resource' => 'reports', 'action' => 'export'],
    ]);

    (new DomainPermissionSynchronizer($initialRegistry))->sync();

    $userRole = Role::query()->where('name', Role::BUILT_IN_USER)->firstOrFail();

    $customPermissionId = Permission::query()
        ->where('resource', 'reports')
        ->where('action', 'export')
        ->value('id');

    expect($customPermissionId)->not->toBeNull();

    $userRole->permissions()->syncWithoutDetaching([$customPermissionId]);

    $updatedRegistry = new PermissionRegistry;
    $updatedRegistry->registerPermissions([
        ['resource' => 'reports', 'action' => 'view', 'built_in_roles' => [Role::BUILT_IN_USER]],
        ['resource' => 'reports', 'action' => 'export'],
        ['resource' => 'tasks', 'action' => 'view', 'built_in_roles' => [Role::BUILT_IN_USER]],
    ]);

    (new DomainPermissionSynchronizer($updatedRegistry))->sync();

    $permissionKeys = $userRole->fresh()
        ->permissions()
        ->get(['resource', 'action'])
        ->map(fn (Permission $permission): string => $permission->resource.'.'.$permission->action)
        ->values()
        ->all();

    expect($permissionKeys)
        ->toContain('reports.view')
        ->toContain('tasks.view')
        ->toContain('reports.export');
});
