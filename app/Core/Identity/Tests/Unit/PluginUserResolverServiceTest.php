<?php

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Contracts\PluginUserResolver;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Auth;

it('returns the current authenticated user as a plugin safe dto', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Current',
        'last_name' => 'User',
        'username' => 'current-user',
        'email' => 'current@example.com',
        'is_active' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'Current Role',
        'description' => 'Role for current-user resolver test.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    $user->roles()->sync([$role->id]);

    Auth::login($user);

    $dto = app(PluginUserResolver::class)->currentUser();

    expect($dto)->not->toBeNull()
        ->and($dto?->toArray())->toBe([
            'id' => $user->id,
            'name' => 'Current User',
            'username' => 'current-user',
            'email' => 'current@example.com',
            'is_active' => true,
            'roles' => ['Current Role'],
        ]);
});

it('returns null for current user when no user is authenticated', function (): void {
    Auth::logout();

    expect(app(PluginUserResolver::class)->currentUser())->toBeNull();
});

it('resolves a list of user ids into plugin safe dtos without duplicates', function (): void {
    $firstUser = User::factory()->create([
        'first_name' => 'First',
        'last_name' => 'User',
        'username' => 'first-user',
        'email' => 'first@example.com',
        'is_active' => true,
    ]);

    $secondUser = User::factory()->create([
        'first_name' => 'Second',
        'last_name' => 'User',
        'username' => 'second-user',
        'email' => 'second@example.com',
        'is_active' => false,
    ]);

    $resolver = app(PluginUserResolver::class);

    $dtos = $resolver->findMany([
        $firstUser->id,
        $secondUser->id,
        $firstUser->id,
        'missing-user-id',
    ]);

    expect(array_map(fn ($dto) => $dto->id, $dtos))->toBe([
        $firstUser->id,
        $secondUser->id,
    ]);

    expect($dtos[0]->toArray())->toMatchArray([
        'name' => 'First User',
        'username' => 'first-user',
        'email' => 'first@example.com',
        'is_active' => true,
    ]);

    expect($dtos[1]->toArray())->toMatchArray([
        'name' => 'Second User',
        'username' => 'second-user',
        'email' => 'second@example.com',
        'is_active' => false,
    ]);
});
