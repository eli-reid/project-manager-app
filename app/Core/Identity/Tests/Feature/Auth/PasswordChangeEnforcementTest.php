<?php

use App\Core\Identity\Livewire\Auth\ForcePasswordChange;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('users who must change their password are redirected to the forced password page', function () {
    $user = User::factory()->create([
        'password_change_required' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.change', absolute: false));
});

test('users without a required password change are redirected away from the forced password page', function () {
    $user = User::factory()->create([
        'password_change_required' => false,
    ]);

    $this->actingAs($user)
        ->get(route('password.change'))
        ->assertRedirect(route('dashboard', absolute: false));
});

test('users can complete the forced password change flow', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'password_change_required' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.change', absolute: false));

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->password_change_required)->toBeFalse();
});
