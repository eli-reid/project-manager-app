<?php

it('defines a dedicated livewire row actions dropdown component', function (): void {
    $source = file_get_contents(__DIR__.'/../../../../../app/Livewire/Ui/RowActionsDropdown.php');

    expect($source)->toContain('class RowActionsDropdown extends Component');
    expect($source)->toContain("return view('livewire.ui.row-actions-dropdown');");
});

it('uses the livewire row actions dropdown mount in the first migrated index views', function (): void {
    $announcements = file_get_contents(__DIR__.'/../../../../../app/Core/Announcement/Resources/Views/livewire/admin/announcements/index.blade.php');
    $users = file_get_contents(__DIR__.'/../../../../../app/Core/Auth/User/Resources/Views/livewire/admin/users/index.blade.php');
    $roles = file_get_contents(__DIR__.'/../../../../../app/Core/Auth/Role/Resources/Views/livewire/admin/roles/index.blade.php');
    $clients = file_get_contents(__DIR__.'/../../../../../app/Domains/Clients/Resources/Views/livewire/admin/clients/index.blade.php');

    expect($announcements)->toContain('<livewire:ui.row-actions-dropdown');
    expect($users)->toContain('<livewire:ui.row-actions-dropdown');
    expect($roles)->toContain('<livewire:ui.row-actions-dropdown');
    expect($clients)->toContain('<livewire:ui.row-actions-dropdown');

    expect($announcements)->not->toContain('<x-ui.row-actions-dropdown');
    expect($users)->not->toContain('<x-ui.row-actions-dropdown');
    expect($roles)->not->toContain('<x-ui.row-actions-dropdown');
    expect($clients)->not->toContain('<x-ui.row-actions-dropdown');
});
