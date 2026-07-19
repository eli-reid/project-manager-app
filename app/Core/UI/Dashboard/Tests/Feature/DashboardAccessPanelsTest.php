<?php

use App\Core\Identity\Models\User;

it('renders access management tabs in the dashboard user management panel', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('dashboard', ['panel' => 'access-users']));

    $response->assertOk()
        ->assertSee('Users')
        ->assertSee('Roles')
        ->assertSee('Email Management')
        ->assertSee(route('dashboard', ['panel' => 'access-users']), false)
        ->assertSee(route('dashboard', ['panel' => 'access-roles']), false)
        ->assertSee(route('dashboard', ['panel' => 'access-email-management']), false);
});

it('renders flux icons in the dashboard access navigation instead of first-letter fallbacks', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('dashboard', ['panel' => 'access-users']));

    $response->assertOk()
        ->assertSee('data-test="dashboard-panel-access-users-link"', false)
        ->assertSee('data-flux-icon', false)
        ->assertDontSee('>U<', false)
        ->assertDontSee('>R<', false)
        ->assertDontSee('>E<', false);
});
