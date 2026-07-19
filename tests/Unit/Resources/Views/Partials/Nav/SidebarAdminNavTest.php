<?php

it('centers the administration header in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/livewire/nav/admin/sidebar.blade.php');

    expect($view)->toContain('@if ($canViewAdminNav)');
    expect($view)->toContain('<flux:sidebar.header class="in-data-flux-sidebar-collapsed-desktop:hidden">');
    expect($view)->toContain('<flux:separator data-flux-separator="admin-header" text="{{ __(\'Administration\') }}" class="text-lg" />');
    expect($view)->toContain('<flux:sidebar.item icon="cog" :href="route(\'admin.settings.index\')" :current="request()->routeIs(\'admin.settings.*\')" wire:navigate data-test="admin-settings-link">');
    expect($view)->toContain(':current="request()->routeIs(\'admin.clients.*\') || request()->routeIs(\'admin.addresses.*\')"');
    expect($view)->toContain('data-test="admin-client-management-sidebar-main-link"');
    expect($view)->toContain("{{ __('Client Management') }}");
    expect($view)->not->toContain('data-test="admin-addresses-sidebar-main-link"');
    expect($view)->toContain(':current="request()->routeIs(\'admin.stock-orders.*\') || request()->routeIs(\'admin.stock-order-templates.*\') || request()->routeIs(\'admin.invoices.*\')"');
    expect($view)->toContain('data-test="admin-stock-invoices-sidebar-main-link"');
    expect($view)->toContain("{{ __('Stock & Invoices') }}");
    expect($view)->toContain('data-test="admin-time-management-sidebar-main-link"');
    expect($view)->toContain(':current="request()->routeIs(\'admin.timecards.*\') || request()->routeIs(\'admin.dailies.*\')"');
    expect($view)->toContain("{{ __('Time Management') }}");
    expect($view)->not->toContain('admin-dailies-sidebar-main-link');
    expect($view)->not->toContain('admin-invoices-sidebar-main-link');
    expect($view)->toContain(':href="$accessManagementHref"');
    expect($view)->toContain('request()->routeIs(\'admin.users.*\') || request()->routeIs(\'admin.roles.*\') || request()->routeIs(\'admin.cpanel.manage.*\')');
    expect($view)->toContain('in_array(request()->query(\'panel\'), [\'access-users\', \'access-roles\', \'access-email-management\'], true)');
});

it('includes a payroll link in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/livewire/nav/admin/sidebar.blade.php');

    expect($view)->toContain(':href="$payrollHref"');
    expect($view)->toContain(':current="request()->routeIs(\'admin.payroll.*\')"');
    expect($view)->toContain('data-test="admin-payroll-sidebar-main-link"');
    expect($view)->toContain('{{ __(\'Payroll\') }}');
    expect($view)->toContain(':href="route(\'admin.reports.index\')"');
    expect($view)->toContain(':current="request()->routeIs(\'admin.reports.*\') || request()->routeIs(\'reports.*\')"');
    expect($view)->toContain('data-test="admin-reports-sidebar-main-link"');
    expect($view)->toContain('{{ __(\'Reports\') }}');
});
