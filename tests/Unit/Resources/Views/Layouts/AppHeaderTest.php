<?php

it('mounts the shared app header livewire component in the layout', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/app.blade.php');

    expect($view)->toContain('<livewire:layouts.app-sidebar />');
});

it('keeps key navigation links in the livewire app header view', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/app-header.blade.php');

    expect($view)->toContain('data-test="timecards-navbar-link"');
    expect($view)->toContain('data-test="projects-sidebar-link-mobile"');
    expect($view)->toContain('data-test="admin-settings-sidebar-link"');
    expect($view)->toContain("@include('auth-user::partials.desktop-user-menu')");
});
