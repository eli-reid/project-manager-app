<?php

it('includes payroll links in the mobile app menu', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/layouts/app/sidebar.blade.php');

    expect($view)->toContain('@can(\'payroll-stubs.view-own\')');
    expect($view)->toContain('data-test="payroll-link-mobile"');
    expect($view)->not->toContain('data-test="payroll-forecasting-link-mobile"');
    expect($view)->not->toContain('data-test="admin-payroll-link-mobile"');
});
