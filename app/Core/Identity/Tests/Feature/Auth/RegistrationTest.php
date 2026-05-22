<?php

use App\Core\Identity\Models\User;
use App\Core\Identity\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+12125551234',
        'username' => 'johndoe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    expect($user->phone)->toEqual('+12125551234');

    Notification::assertNotSentTo($user, UserInvitationNotification::class);
});
