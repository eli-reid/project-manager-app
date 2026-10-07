<?php

use App\Core\Identity\Livewire\Auth\User\DesktopUserMenu;
use App\Core\Identity\Livewire\Auth\User\MobileUserMenu;
use App\Core\Identity\Models\User;
use Livewire\Livewire;

it('renders the desktop user menu variant', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(DesktopUserMenu::class)
        ->assertSee($user->name)
        ->assertSee('Settings');
});

it('renders the mobile user menu variant', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(MobileUserMenu::class)
        ->assertSee($user->name)
        ->assertSee('Settings');
});

it('places the livewire root attributes on the outermost mobile user menu element', function (bool $authenticated): void {
    if ($authenticated) {
        $this->actingAs(User::factory()->create());
    }

    $html = trim(Livewire::mount('auth.user.mobile-user-menu'));

    // A nested element receiving wire:id (e.g. the dropdown's button) makes the
    // browser throw "Snapshot missing on Livewire component" after lazy loading.
    expect($html)->toMatch('/^<div\b[^>]*\bwire:snapshot=/')
        ->and(substr_count($html, 'wire:id='))->toBe(1);
})->with([
    'authenticated' => true,
    'guest' => false,
]);
