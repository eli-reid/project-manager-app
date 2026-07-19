<?php

use App\Core\Identity\Livewire\Layouts\AccessAdmin;
use App\Core\Identity\Models\User;

it('links access admin tabs to dashboard panel routes', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    $linksByLabel = collect(AccessAdmin::navbarItems())
        ->pluck('href', 'label')
        ->all();

    expect($linksByLabel)->toMatchArray([
        'Users' => route('dashboard', ['panel' => 'access-users']),
        'Roles' => route('dashboard', ['panel' => 'access-roles']),
        'Email Management' => route('dashboard', ['panel' => 'access-email-management']),
    ]);
});
