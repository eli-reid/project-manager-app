<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;

it('registers sprint zero foundation permission matrix entries', function (): void {
    app(DomainPermissionSynchronizer::class)->sync();

    $expectedPermissions = [
        'navigation.view-admin',
        'project-access.view',
        'project-access.grant',
        'project-access.revoke',
        'change-orders.view',
        'change-orders.approve',
        'rate-management.view',
        'rate-management.edit',
        'document-sharing.create',
        'document-sharing.revoke',
        'vendors.view',
        'vendors.deactivate',
    ];

    $existingKeys = Permission::query()
        ->select(['resource', 'action'])
        ->get()
        ->map(fn (Permission $permission): string => $permission->resource.'.'.$permission->action)
        ->all();

    foreach ($expectedPermissions as $expectedPermission) {
        expect($existingKeys)->toContain($expectedPermission);
    }
});
