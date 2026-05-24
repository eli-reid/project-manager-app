<?php

use App\Core\Identity\Livewire\Settings\Mobile\SettingsTabs;
use App\Core\Identity\Livewire\Settings\SettingsHeading;
use App\Core\Identity\Models\User;
use Livewire\Livewire;

it('renders the shared settings heading child component', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(SettingsHeading::class)
        ->assertViewIs('partials.settings-heading')
        ->assertSee('Settings')
        ->assertSee('Manage your profile and account settings');
});

it('renders the shared mobile settings tabs child component', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(SettingsTabs::class)
        ->assertViewIs('core-user::livewire.mobile.settings.tabs')
        ->assertSee('Profile')
        ->assertSee('Password')
        ->assertSee('Notifications')
        ->assertSee('Appearance');
});
