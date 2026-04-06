<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Support\Facades\Gate;

it('allows admins to view and update settings', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    expect(Gate::forUser($admin)->allows('viewAny', SettingsSqlite::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', SettingsSqlite::class))->toBeTrue();
});

it('forbids non-admin users from viewing and updating settings by default', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($nonAdmin)->allows('viewAny', SettingsSqlite::class))->toBeFalse()
        ->and(Gate::forUser($nonAdmin)->allows('update', SettingsSqlite::class))->toBeFalse();
});
