<?php

use App\Core\Identity\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('allows admin to access /admin/users', function () {
    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'Test',
        'email' => 'perm.admin@example.com',
        'username' => 'perm.admin',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => true,
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get('/admin/users');
    expect($response->status())->toBe(200);
});

it('denies regular user access to /admin/users', function () {
    $regular = User::create([
        'first_name' => 'Regular',
        'last_name' => 'User',
        'email' => 'perm.regular@example.com',
        'username' => 'perm.regular',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => true,
    ]);

    $response = actingAs($regular)->get('/admin/users');
    expect($response->status())->toBeIn([403, 302]);
});

it('denies inactive user access to /admin/users', function () {
    $inactive = User::create([
        'first_name' => 'Inactive',
        'last_name' => 'User',
        'email' => 'perm.inactive@example.com',
        'username' => 'perm.inactive',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => false,
    ]);

    $response = actingAs($inactive)->get('/admin/users');
    expect($response->status())->toBeIn([403, 302, 401]);
});

it('allows admin to access /admin/settings', function () {
    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'Settings',
        'email' => 'perm.admin.settings@example.com',
        'username' => 'perm.admin.settings',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => true,
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get('/admin/settings');
    expect($response->status())->toBe(200);
});

it('denies regular user access to /admin/settings', function () {
    $regular = User::create([
        'first_name' => 'Regular',
        'last_name' => 'Settings',
        'email' => 'perm.regular.settings@example.com',
        'username' => 'perm.regular.settings',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => true,
    ]);

    $response = actingAs($regular)->get('/admin/settings');
    expect($response->status())->toBeIn([403, 302]);
});

it('allows admin to access /admin/projects', function () {
    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'Projects',
        'email' => 'perm.admin.projects@example.com',
        'username' => 'perm.admin.projects',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => true,
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get('/admin/projects');
    expect($response->status())->toBe(200);
});

it('denies regular user access to /admin/projects', function () {
    $regular = User::create([
        'first_name' => 'Regular',
        'last_name' => 'Projects',
        'email' => 'perm.regular.projects@example.com',
        'username' => 'perm.regular.projects',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => true,
    ]);

    $response = actingAs($regular)->get('/admin/projects');
    expect($response->status())->toBeIn([403, 302]);
});

it('rejects negative quantity in stock order', function () {
    $regular = User::create([
        'first_name' => 'Regular',
        'last_name' => 'StockNeg',
        'email' => 'perm.regular.stock@example.com',
        'username' => 'perm.regular.stock',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => true,
    ]);

    $response = actingAs($regular)->post('/stock-orders', [
        'project_id' => '01kkssak2gga7kbx8s195nnya4',
        'quantity' => -100,
        'item_description' => 'Test item',
    ]);
    
    expect($response->status())->toBeIn([400, 403, 422]);
});

it('handles invalid ULID in URL gracefully', function () {
    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'ULID',
        'email' => 'perm.admin.ulid@example.com',
        'username' => 'perm.admin.ulid',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => true,
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get('/admin/users/INVALID-ULID-12345');
    expect($response->status())->toBe(404);
});

it('prevents regular user from setting is_admin=true', function () {
    $regular = User::create([
        'first_name' => 'Regular',
        'last_name' => 'Escalate',
        'email' => 'perm.regular.escalate@example.com',
        'username' => 'perm.regular.escalate',
        'password' => bcrypt('FuzzTestPass123!'),
        'is_admin' => false,
        'is_active' => true,
    ]);

    $response = actingAs($regular)->put("/admin/users/{$regular->id}", [
        'is_admin' => true,
    ]);
    
    expect($response->status())->toBeIn([403, 302, 422]);
    
    $regular->refresh();
    expect($regular->is_admin)->toBeFalse();
});
