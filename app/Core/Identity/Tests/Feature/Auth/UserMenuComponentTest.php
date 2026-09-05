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
