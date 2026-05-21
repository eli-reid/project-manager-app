<?php

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('mobile desktop routes', [
    'dashboard' => ['dashboard', 'mobile.dashboard'],
    'projects index' => ['projects.index', 'projects.mobile.index'],
    'dailies index' => ['dailies.index', 'dailies.mobile.index'],
    'documents index' => ['documents.index', 'documents.mobile.global'],
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

it('redirects admins on mobile to the mobile dashboard', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])
        ->get(route('dashboard'));

    $response->assertRedirect(route('mobile.dashboard'));
});

it('redirects livewire navigation requests on mobile to mobile routes', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
            'X-Livewire-Navigate' => 'true',
        ])
        ->get(route('dailies.index'));

    $response->assertRedirect(route('dailies.mobile.index'));
});
