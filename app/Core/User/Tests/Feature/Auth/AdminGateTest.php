<?php

use App\Core\User\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows admin ability checks only for admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($admin)->allows('admin'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser($nonAdmin)->allows('admin'))->toBeFalse()
        ->and(Gate::forUser($nonAdmin)->allows('access-admin'))->toBeFalse();
});
