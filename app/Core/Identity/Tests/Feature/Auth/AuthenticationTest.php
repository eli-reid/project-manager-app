<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

test('home route renders the login screen for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Email or username')
        ->assertSee('Log in to your account');
});

test('authenticated users are redirected from home to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard', absolute: false));
});

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login screen responses are not cached', function () {
    $response = $this->get(route('login'));
    $cacheControlHeader = (string) $response->headers->get('Cache-Control');

    $response
        ->assertOk()
        ->assertHeader('Pragma', 'no-cache');

    expect($cacheControlHeader)
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0');
});

test('home route guest login screen responses are not cached', function () {
    $response = $this->get(route('home'));
    $cacheControlHeader = (string) $response->headers->get('Cache-Control');

    $response
        ->assertOk()
        ->assertHeader('Pragma', 'no-cache');

    expect($cacheControlHeader)
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0');
});

test('users can authenticate using email on the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.login',
        'actor_id' => (string) $user->id,
        'target_id' => (string) $user->id,
    ]);

    expect(AuditLog::query()->where('action', 'auth.login')->first()?->metadata)->toMatchArray([
        'guard' => 'web',
        'remember' => false,
    ]);
});

test('users can authenticate using username on the login screen', function () {
    $user = User::factory()->create([
        'username' => 'casey.jones',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'casey.jones',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('login');

    $this->assertGuest();
});

test('login attempts are throttled after repeated failures', function () {
    $user = User::factory()->create();

    $throttleKey = Str::transliterate(Str::lower($user->email.'|127.0.0.1'));
    RateLimiter::clear($throttleKey);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('login');
    }

    expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeTrue();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertTooManyRequests();

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.logout',
        'actor_id' => (string) $user->id,
        'target_id' => (string) $user->id,
    ]);
});

test('mobile users are redirected to the mobile dashboard after login', function () {
    $user = User::factory()->create();

    $response = $this
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1')
        ->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('mobile.dashboard', absolute: false));

    $this->assertAuthenticated();
});
