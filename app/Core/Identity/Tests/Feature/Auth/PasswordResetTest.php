<?php

use App\Core\Audit\Models\AuditLog;
use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Actions\Fortify\ResetUserPassword;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
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

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password.reset',
            'actor_id' => (string) $user->id,
            'target_id' => (string) $user->id,
        ]);

        expect(AuditLog::query()->where('action', 'auth.password.reset')->latest('created_at')->first()?->after)
            ->toMatchArray(['password_change_required' => false]);

        return true;
    });
});

test('password reset token expires after configured window', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->travel(31)->minutes();

        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors('email');

        return true;
    });
});

test('forgot-password reset syncs cpanel mailbox password when sync is enabled', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.sync_user_passwords', 'true');
    Settings::set('cpanel.verify_ssl', 'true');

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
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.sync_user_passwords', 'false');
    Settings::set('cpanel.verify_ssl', 'true');

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

test('forgot-password reset does not fail when cpanel sync throws', function () {
    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
        'password_change_required' => true,
    ]);

    $this->mock(CpanelMailboxManager::class, function ($mock) use ($user): void {
        $mock->shouldIgnoreMissing();

        $mock->shouldReceive('syncPasswordForUser')
            ->once()
            ->withArgs(function (User $targetUser, string $password) use ($user): bool {
                return $targetUser->is($user) && $password === 'new-password';
            })
            ->andThrow(new RuntimeException('cPanel is unavailable'));
    });

    app(ResetUserPassword::class)->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect(Hash::check('new-password', (string) $user->fresh()?->password))->toBeTrue()
        ->and($user->fresh()?->password_change_required)->toBeFalse();
});
