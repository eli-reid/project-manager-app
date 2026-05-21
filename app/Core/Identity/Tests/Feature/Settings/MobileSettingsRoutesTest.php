<?php

use App\Core\Identity\Models\User;
use Laravel\Fortify\Features;

it('renders all mobile settings tabs for verified users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.mobile.profile'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.mobile.password'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.mobile.notifications'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.mobile.appearance'))
        ->assertOk();
});

it('matches desktop access behavior for verified-only mobile settings tabs', function (): void {
    $user = User::factory()->unverified()->create();

    $desktopPasswordStatus = $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->getStatusCode();

    $desktopNotificationsStatus = $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->getStatusCode();

    $desktopAppearanceStatus = $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->getStatusCode();

    expect(
        $this->actingAs($user)
            ->get(route('settings.mobile.password'))
            ->getStatusCode()
    )->toBe($desktopPasswordStatus);

    expect(
        $this->actingAs($user)
            ->get(route('settings.mobile.notifications'))
            ->getStatusCode()
    )->toBe($desktopNotificationsStatus);

    expect(
        $this->actingAs($user)
            ->get(route('settings.mobile.appearance'))
            ->getStatusCode()
    )->toBe($desktopAppearanceStatus);
});

it('enforces two-factor password confirmation on mobile when enabled', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.mobile.two-factor'))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.mobile.two-factor'))
        ->assertOk();
});
