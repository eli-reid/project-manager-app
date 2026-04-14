<?php

it('centers the administration header in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-admin-nav.blade.php');

    expect($view)->toContain('<flux:sidebar.header class="in-data-flux-sidebar-collapsed-desktop:hidden">');
    expect($view)->toContain('<flux:separator  data-flux-separator="admin-header" text="{{ __(\'Administration\') }}" class="text-lg" />');
    expect($view)->not->toContain('<flux:sidebar.group');
    expect($view)->toContain('<flux:sidebar.item icon="cog" :href="route(\'admin.settings.index\')" :current="request()->routeIs(\'admin.settings.*\')" wire:navigate data-test="admin-settings-link">');
    expect($view)->toContain(':current="request()->routeIs(\'admin.clients.*\') || request()->routeIs(\'admin.addresses.*\')"');
    expect($view)->toContain('data-test="admin-client-management-sidebar-main-link"');
    expect($view)->toContain("{{ __('Client Management') }}");
    expect($view)->toContain(':current="request()->routeIs(\'admin.stock-orders.*\') || request()->routeIs(\'admin.stock-order-templates.*\') || request()->routeIs(\'admin.invoices.*\')"');
    expect($view)->toContain('data-test="admin-stock-invoices-sidebar-main-link"');
    expect($view)->toContain("{{ __('Stock & Invoices') }}");
    expect($view)->toContain(':current="request()->routeIs(\'admin.timecards.*\') || request()->routeIs(\'admin.dailies.*\')"');
    expect($view)->toContain('data-test="admin-time-management-sidebar-main-link"');
    expect($view)->toContain("{{ __('Time Management') }}");
    expect($view)->not->toContain('admin-dailies-sidebar-main-link');
    expect($view)->not->toContain('admin-invoices-sidebar-main-link');
    expect($view)->not->toContain('request()->routeIs(\'admin.users.*\') || request()->routeIs(\'admin.roles.*\') || request()->routeIs(\'admin.settings.*\')');
});

it('includes a payroll link in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-admin-nav.blade.php');

    expect($view)->toContain('$canManagePayroll = ($user?->can(\'payroll-rates.view\') ?? false)');
    expect($view)->toContain(':href="$user?->can(\'payroll-rates.view\')');
    expect($view)->toContain(':current="request()->routeIs(\'admin.payroll.*\')"');
    expect($view)->toContain('data-test="admin-payroll-sidebar-main-link"');
    expect($view)->toContain('{{ __(\'Payroll\') }}');
    expect($view)->toContain('$canViewReports = ($user?->can(\'reports.financial.view\') ?? false)');
    expect($view)->toContain(':href="$user?->can(\'reports.financial.view\') ? route(\'reports.financial.index\') : route(\'reports.operational.index\')"');
    expect($view)->toContain(':current="request()->routeIs(\'reports.*\')"');
    expect($view)->toContain('data-test="admin-reports-sidebar-main-link"');
    expect($view)->toContain('{{ __(\'Reports\') }}');
});
