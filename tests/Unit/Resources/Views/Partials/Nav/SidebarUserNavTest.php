<?php

it('includes a dailies link in the user sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-user-nav.blade.php');

    expect($view)->toContain('@can(\'viewAny\', \\App\\Domains\\Dailies\\Models\\DailyReport::class)');
    expect($view)->toContain('<flux:sidebar.item');
    expect($view)->toContain('icon="clipboard-document-list"');
    expect($view)->toContain(':href="route(\'dailies.index\')"');
    expect($view)->toContain(':current="request()->routeIs(\'dailies.*\')"');
    expect($view)->toContain("{{ __('My Dailies') }}");
});

it('includes a payroll history link in the user sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-user-nav.blade.php');

    expect($view)->not->toContain("@can('payroll.view')");
    expect($view)->not->toContain(':href="route(\'payroll.history\')"');
    expect($view)->not->toContain('data-test="payroll-sidebar-main-link"');
    expect($view)->not->toContain("{{ __('My Payroll') }}");
});
