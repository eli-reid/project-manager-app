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

    expect($view)->toContain('auth()->user()?->can(\'payroll-rates.view\') || auth()->user()?->can(\'payroll-timecards.view\') || auth()->user()?->can(\'payroll-runs.preview\')');
    expect($view)->toContain(':href="auth()->user()?->can(\'payroll-rates.view\')');
    expect($view)->toContain(':current="request()->routeIs(\'admin.payroll.*\')"');
    expect($view)->toContain('data-test="admin-payroll-sidebar-main-link"');
    expect($view)->toContain('{{ __(\'Payroll\') }}');
});
