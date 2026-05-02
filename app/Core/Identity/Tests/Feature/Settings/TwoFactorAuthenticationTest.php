<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Identity\Livewire\Settings\TwoFactor;
use App\Core\Identity\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('two factor settings page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertSee('Two-factor authentication')
        ->assertSee('Disabled');
});

test('two factor settings page requires password confirmation when enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('two-factor.show'));

    $response->assertRedirect(route('password.confirm'));
});

test('two factor settings page returns forbidden response when two factor is disabled', function () {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'));

    $response->assertForbidden();
});

test('two factor authentication disabled when confirmation abandoned between requests', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    $component = Livewire::test(TwoFactor::class);

    $component->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('two factor disable action writes an audit log', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($user);

    Livewire::test(TwoFactor::class)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.two-factor.disabled',
        'actor_id' => (string) $user->id,
        'target_id' => (string) $user->id,
    ]);
});

test('two factor enable without confirmation writes an audit log', function () {
    Features::twoFactorAuthentication([
        'confirm' => false,
        'confirmPassword' => false,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSet('twoFactorEnabled', true);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.two-factor.enabled',
        'actor_id' => (string) $user->id,
        'target_id' => (string) $user->id,
    ]);

    expect(AuditLog::query()->where('action', 'auth.two-factor.enabled')->latest('created_at')->first()?->metadata)
        ->toMatchArray(['confirmed' => false]);
});
