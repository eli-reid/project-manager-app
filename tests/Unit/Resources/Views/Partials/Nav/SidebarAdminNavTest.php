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

    expect($view)->toContain('@can(\'payroll-rates.view\')');
    expect($view)->toContain(':href="route(\'admin.payroll.rates.index\')"');
    expect($view)->toContain('data-test="admin-payroll-rates-sidebar-main-link"');
    expect($view)->toContain(':href="route(\'admin.payroll.rate-types.index\')"');
    expect($view)->toContain('data-test="admin-payroll-rate-types-sidebar-main-link"');
    expect($view)->toContain('@can(\'payroll-timecards.view\')');
    expect($view)->toContain(':href="route(\'admin.payroll.timecards.review\')"');
    expect($view)->toContain('data-test="admin-payroll-timecards-sidebar-main-link"');
    expect($view)->toContain('@can(\'payroll-runs.preview\')');
    expect($view)->toContain(':href="route(\'admin.payroll.runs.index\')"');
    expect($view)->toContain('data-test="admin-payroll-runs-sidebar-main-link"');
});
