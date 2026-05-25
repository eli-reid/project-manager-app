<?php

it('includes a dailies link in the user sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/livewire/nav/user/sidebar.blade.php');

    expect($view)->toContain('$canViewDailies = $user?->can(\'viewAny\', \\App\\Domains\\Dailies\\Models\\DailyReport::class) ?? false;');
    expect($view)->not->toContain('$showWorkGroup');
    expect($view)->not->toContain('$showOperationsGroup');
    expect($view)->toContain('<flux:sidebar.item');
    expect($view)->toContain('icon="clipboard-document-list"');
    expect($view)->toContain(':href="route(\'dailies.index\')"');
    expect($view)->toContain(':current="request()->routeIs(\'dailies.*\')"');
    expect($view)->toContain("{{ __('My Dailies') }}");
});

it('does not include a payroll history link in the user sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/livewire/nav/user/sidebar.blade.php');

    expect($view)->not->toContain("@can('payroll-stubs.view-own')");
    expect($view)->not->toContain(':href="route(\'payroll.history\')"');
    expect($view)->not->toContain('data-test="payroll-sidebar-main-link"');
    expect($view)->not->toContain("{{ __('My Payroll') }}");
});

it('does not include a payroll forecasting link in the user sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/livewire/nav/user/sidebar.blade.php');

    expect($view)->not->toContain("@can('reports.payroll.view')");
    expect($view)->not->toContain(':href="route(\'reports.payroll.forecasting.index\')"');
    expect($view)->not->toContain('data-test="payroll-forecasting-sidebar-link"');
    expect($view)->not->toContain("{{ __('Payroll Forecasting') }}");
    expect($view)->not->toContain('$canViewReports = $user?->can(\'reports.financial.view\') ?? false;');
    expect($view)->not->toContain(':href="route(\'reports.financial.index\')"');
    expect($view)->not->toContain('data-test="reports-sidebar-main-link"');
    expect($view)->not->toContain("{{ __('Reports') }}");
});
