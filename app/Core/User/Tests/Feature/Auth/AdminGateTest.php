<?php

use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use Illuminate\Support\Facades\Gate;

it('allows admin ability checks only for admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($admin)->allows('admin'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser($nonAdmin)->allows('admin'))->toBeFalse()
        ->and(Gate::forUser($nonAdmin)->allows('access-admin'))->toBeFalse();
});

it('allows admin ability checks for users with built-in admin role', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);
    $adminRoleId = \App\Core\User\Models\Role::query()
        ->where('name', \App\Core\User\Models\Role::BUILT_IN_ADMIN)
        ->value('id');

    $user->roles()->sync([$adminRoleId]);

    expect(Gate::forUser($user->fresh())->allows('admin'))->toBeTrue()
        ->and(Gate::forUser($user->fresh())->allows('access-admin'))->toBeTrue();
});
