<?php

use App\Core\User\Actions\Fortify\ResetUserPassword;
use App\Core\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));
        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password_change_required' => true,
    ]);

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        expect($user->fresh()->password_change_required)->toBeFalse();

        return true;
    });
});

test('forgot-password reset syncs cpanel mailbox password when sync is enabled', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'sync_user_passwords' => true,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    app(ResetUserPassword::class)->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/passwd_pop'
            && ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test'
            && ($data['password'] ?? null) === 'new-password';
    });
});

test('forgot-password reset does not sync cpanel mailbox password when sync is disabled', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'sync_user_passwords' => false,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake();

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    app(ResetUserPassword::class)->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    Http::assertNothingSent();
});
