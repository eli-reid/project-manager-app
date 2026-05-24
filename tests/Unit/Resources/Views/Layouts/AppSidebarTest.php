<?php

it('mounts the shared app sidebar livewire component in the layout', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/layouts/app/sidebar.blade.php');

    expect($view)->toContain('<livewire:users::layout.app-sidebar />');
    expect($view)->toContain('lg:ms-64');
});

it('includes payroll links in the mobile app sidebar component menu', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../app/Core/Auth/User/Resources/Views/livewire/layout/app-sidebar.blade.php');

    expect($view)->toContain('@can(\'payroll-stubs.view-own\')');
    expect($view)->toContain('data-test="payroll-link-mobile"');
    expect($view)->not->toContain('data-test="reports-link-mobile"');
    expect($view)->not->toContain(':href="route(\'reports.financial.index\')"');
    expect($view)->not->toContain('data-test="payroll-forecasting-link-mobile"');
    expect($view)->not->toContain('data-test="admin-payroll-link-mobile"');
    expect($view)->not->toContain(':href="route(\'admin.settings.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.users.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.roles.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.documents.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.scheduler.tasks.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.queue.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.announcements.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.timecards.index\')"');
    expect($view)->not->toContain(':href="route(\'admin.invoices.index\')"');
});
