<?php

use App\Core\User\Models\User;

it('registers admin settings route names for navigation and browser testing', function () {
    expect(route('admin.settings.index', absolute: false))->toBe('/admin/settings')
        ->and(route('admin.settings.import', absolute: false))->toBe('/admin/settings/import')
        ->and(route('admin.settings.export', absolute: false))->toBe('/admin/settings/export');
});

it('renders settings index in app layout for admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Settings Management');
});

it('redirects guests to login for settings index', function () {
    $this->get(route('admin.settings.index'))
        ->assertRedirect(route('login'));
});

it('forbids non-admin users from settings index', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    $this->actingAs($nonAdmin)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});
