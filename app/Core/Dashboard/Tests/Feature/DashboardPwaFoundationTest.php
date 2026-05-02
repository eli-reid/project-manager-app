<?php

use App\Core\Identity\Models\User;

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
        ->assertSee('Install App')
        ->assertSee('Dashboard');
});
