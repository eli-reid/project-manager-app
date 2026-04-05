<?php

use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows admin ability checks only for admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($admin)->allows('admin'))->toBeTrue()
        ->and(Gate::forUser($nonAdmin)->allows('admin'))->toBeFalse();
});

it('allows admin ability checks for users with built-in admin role', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);
    $adminRoleId = Role::query()
        ->where('name', Role::BUILT_IN_ADMIN)
        ->value('id');

    $user->roles()->sync([$adminRoleId]);

    expect(Gate::forUser($user->fresh())->allows('admin'))->toBeTrue();
});
