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

it('redirects authenticated mobile browsers from dashboard to mobile dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1')
        ->get(route('dashboard'))
        ->assertRedirect(route('mobile.dashboard', absolute: false));
});

it('keeps authenticated desktop browsers on dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard::index');
});
