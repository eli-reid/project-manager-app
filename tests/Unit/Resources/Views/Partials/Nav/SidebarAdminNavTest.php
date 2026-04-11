<?php

it('centers the administration header in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-admin-nav.blade.php');

    expect($view)->toContain('<flux:sidebar.header class="in-data-flux-sidebar-collapsed-desktop:hidden">');
    expect($view)->toContain('<flux:separator  data-flux-separator="admin-header" text="{{ __(\'Administration\') }}" class="text-lg" />');
    expect($view)->toContain('<flux:sidebar.item icon="cog" :href="route(\'admin.settings.index\')" :current="request()->routeIs(\'admin.settings.*\')" wire:navigate data-test="admin-settings-link">');
    expect($view)->toContain('<flux:sidebar.item icon="clipboard-document-list" :href="route(\'admin.dailies.index\')" :current="request()->routeIs(\'admin.dailies.*\')" wire:navigate data-test="admin-dailies-sidebar-main-link">');
    expect($view)->not->toContain('request()->routeIs(\'admin.users.*\') || request()->routeIs(\'admin.roles.*\') || request()->routeIs(\'admin.settings.*\')');
});

it('includes a payroll link in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-admin-nav.blade.php');

    expect($view)->not->toContain("auth()->user()?->hasPermission('payroll.manage')");
    expect($view)->not->toContain(':href="route(\'admin.payroll.periods.index\')"');
    expect($view)->not->toContain('data-test="admin-payroll-sidebar-main-link"');
});
