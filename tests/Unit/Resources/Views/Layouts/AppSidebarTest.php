<?php

it('includes payroll links in the mobile app menu', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/layouts/app/sidebar.blade.php');

    expect($view)->not->toContain('@can(\'payroll.view\')');
    expect($view)->not->toContain('data-test="payroll-link-mobile"');
    expect($view)->not->toContain('data-test="admin-payroll-link-mobile"');
});
