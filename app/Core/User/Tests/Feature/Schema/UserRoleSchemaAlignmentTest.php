<?php

use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use Illuminate\Support\Facades\Schema;

it('has tables and columns required by user and role models', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('user_roles'))->toBeTrue()
        ->and(Schema::hasTable('role_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'id',
            'first_name',
            'last_name',
            'username',
            'email',
            'password',
            'is_admin',
            'is_built_in',
            'is_active',
            'password_change_required',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('roles', [
            'id',
            'name',
            'description',
            'is_active',
            'built_in',
            'access_level',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('permissions', [
            'id',
            'resource',
            'action',
            'label',
            'description',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('user_roles', ['user_id', 'role_id']))->toBeTrue()
        ->and(Schema::hasColumns('role_permissions', ['role_id', 'permission_id']))->toBeTrue();
});

it('resolves role permission checks against resource and action columns', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $adminRole = Role::query()->where('name', Role::BUILT_IN_ADMIN)->firstOrFail();

    expect($adminRole->hasPermission('users.view'))->toBeTrue()
        ->and($adminRole->hasPermission('invalid-permission-format'))->toBeFalse();
});
