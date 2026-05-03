<?php

use App\Core\Identity\Models\User;

it('redirects guests away from dashboard routes', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login', absolute: false));

    $this->get(route('mobile.dashboard'))
        ->assertRedirect(route('login', absolute: false));
});

it('allows unverified users to access dashboard routes', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk();
});

it('renders pwa metadata on the dashboard shell', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('rel="manifest" href="/manifest.json"', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee('viewport-fit=cover', false);
});

it('renders the mobile dashboard shell for authenticated users', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk()
        ->assertSee('data-pwa-mobile-nav', false)
        ->assertSee('data-pwa-install-action', false)
        ->assertSee('Offline mode')
        ->assertSee('Install App')
        ->assertSee('Dashboard');
});
