<?php

use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Livewire\Admin\Roles\Users;
use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Livewire\Admin\Users\Form;
use App\Core\Identity\Models\User;
use App\Core\Identity\Notifications\UserInvitationNotification;
use App\Core\Settings\Facades\Settings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('renders admin users and roles pages for admin users', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertSuccessful()
        ->assertSee('User Management');

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('Role Management');
});

it('forbids non-admin users from admin users and roles pages', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

it('allows creating users and roles through livewire forms', function () {
    app(DomainPermissionSynchronizer::class)->sync();
    Notification::fake();
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $admin = User::factory()->create(['is_admin' => true]);
    $activeRoleId = Role::query()->where('is_active', true)->value('id');

    $this->actingAs($admin);

    Livewire::test(Form::class)
        ->set('first_name', 'Casey')
        ->set('last_name', 'Jones')
        ->set('username', 'casey.jones')
        ->set('email', 'casey@example.com')
        ->set('selectedRoleIds', [$activeRoleId])
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'casey@example.com')->exists())->toBeTrue();

    $createdUser = User::query()->where('email', 'casey@example.com')->firstOrFail();
    expect($createdUser->password_change_required)->toBeTrue()
        ->and($createdUser->roles()->whereKey($activeRoleId)->exists())->toBeTrue();

    Notification::assertSentTo($createdUser, function (UserInvitationNotification $notification, array $channels): bool {
        return $channels === ['mail'];
    });

    Livewire::test(App\Core\Auth\Role\Livewire\Admin\Roles\Form::class)
        ->set('name', 'Field Manager')
        ->set('description', 'Can manage field operations')
        ->set('access_level', 45)
        ->set('is_active', true)
        ->set('selectedPermissionIds', [])
        ->call('save');

    expect(Role::query()->where('name', 'Field Manager')->exists())->toBeTrue();
});

it('allows assigning and removing users from a role through livewire', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $admin = User::factory()->create(['is_admin' => true]);
    $role = Role::query()->where('built_in', false)->first()
        ?? Role::query()->create([
            'name' => 'Crew Lead',
            'description' => 'Crew lead role',
            'is_active' => true,
            'built_in' => false,
            'access_level' => 20,
        ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Users::class, ['role' => $role])
        ->set('selectedUserIds', [$userA->id, $userB->id])
        ->call('assignSelectedUsers')
        ->assertHasNoErrors();

    expect($role->users()->whereKey($userA->id)->exists())->toBeTrue()
        ->and($role->users()->whereKey($userB->id)->exists())->toBeTrue();

    Livewire::test(Users::class, ['role' => $role])
        ->call('removeUser', $userA->id)
        ->assertHasNoErrors();

    expect($role->users()->whereKey($userA->id)->exists())->toBeFalse()
        ->and($role->users()->whereKey($userB->id)->exists())->toBeTrue();
});

it('forbids non-admin users from mutating roles via direct livewire requests', function () {
    app(DomainPermissionSynchronizer::class)->sync();

    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);

    expect(
        Gate::forUser($nonAdmin)->allows('create', Role::class)
    )->toBeFalse();

    expect(
        Gate::forUser($admin)->allows('create', Role::class)
    )->toBeTrue();
});
