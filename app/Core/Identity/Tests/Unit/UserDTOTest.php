<?php

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\DTO\UserDTO;
use App\Core\Identity\Models\User;

it('builds a plugin safe payload from a user model', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Plugin',
        'last_name' => 'User',
        'username' => 'plugin-user',
        'email' => 'plugin@example.com',
        'is_active' => true,
        'password_change_required' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'Plugin Consumer',
        'description' => 'Role used to verify UserDTO role exposure.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    $user->roles()->sync([$role->id]);

    $dto = UserDTO::fromUser($user->fresh());

    expect($dto->toArray())->toBe([
        'id' => $user->id,
        'name' => 'Plugin User',
        'username' => 'plugin-user',
        'email' => 'plugin@example.com',
        'is_active' => true,
        'roles' => ['Plugin Consumer'],
    ]);

    expect(array_keys($dto->toArray()))
        ->not->toContain('password')
        ->not->toContain('remember_token')
        ->not->toContain('two_factor_secret')
        ->not->toContain('two_factor_recovery_codes')
        ->not->toContain('password_change_required');
});

it('serializes to the same safe payload for plugins', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Fallback',
        'last_name' => 'Name',
        'username' => 'fallback-user',
        'email' => 'fallback@example.com',
        'is_active' => false,
    ]);

    $dto = UserDTO::fromUser($user);

    expect($dto->jsonSerialize())->toBe($dto->toArray());
});
