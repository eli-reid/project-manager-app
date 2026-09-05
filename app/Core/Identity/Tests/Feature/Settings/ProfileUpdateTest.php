<?php

use App\Core\Identity\Livewire\Settings\DeleteUserForm;
use App\Core\Identity\Livewire\Settings\Profile;
use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('mobile profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/mobile/settings/profile')->assertOk();
});

test('profile settings pages do not show delete account action', function () {
    $desktopSettingsView = file_get_contents(app_path('Core/Identity/Resources/Views/livewire/settings/profile.blade.php'));
    $mobileSettingsView = file_get_contents(app_path('Core/Identity/Resources/Views/livewire/mobile/settings/profile.blade.php'));

    expect($desktopSettingsView)
        ->toBeString()
        ->not->toContain('livewire:settings::delete-user-form')
        ->not->toContain('Delete account');

    expect($mobileSettingsView)
        ->toBeString()
        ->not->toContain('livewire:settings::delete-user-form')
        ->not->toContain('Delete account');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $originalFirstName = $user->first_name;
    $originalLastName = $user->last_name;

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('first_name', 'Changed')
        ->set('last_name', 'Name')
        ->set('profile_addresses', [])
        ->set('phone', '+13035550123')
        ->set('username', 'test-user')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->first_name)->toEqual($originalFirstName);
    expect($user->last_name)->toEqual($originalLastName);
    expect($user->phone)->toEqual('+13035550123');
    expect($user->username)->toEqual('test-user');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('first_name', 'Test')
        ->set('last_name', 'User')
        ->set('profile_addresses', [])
        ->set('phone', (string) ($user->phone ?? ''))
        ->set('username', $user->username)
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can manage profile addresses', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('profile_addresses', [
            [
                'id' => null,
                'address1' => '42 Profile Way',
                'address2' => '',
                'city' => 'Boulder',
                'state' => 'CO',
                'zip' => '80301',
                'country' => 'US',
            ],
        ])
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $address = Address::query()->where('address1', '42 Profile Way')->first();

    expect($address)->not->toBeNull()
        ->and($user->fresh()->addresses()->where('addresses.id', $address?->id)->exists())->toBeTrue();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
