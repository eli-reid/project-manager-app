<?php

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('mobile desktop routes', [
    'dashboard' => ['dashboard', 'mobile.dashboard'],
    'projects index' => ['projects.index', 'projects.mobile.index'],
    'documents index' => ['documents.index', 'documents.mobile.global'],
    'change orders index' => ['change-orders.index', 'mobile.dashboard'],
]);

it('redirects normal users on mobile to the mobile surface', function (string $routeName, string $expectedRouteName): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])
        ->get(route($routeName));

    $response->assertRedirect(route($expectedRouteName));
})->with('mobile desktop routes');

it('keeps admins on the desktop dashboard even on mobile', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])
        ->get(route('dashboard'));

    $response->assertOk();
});
