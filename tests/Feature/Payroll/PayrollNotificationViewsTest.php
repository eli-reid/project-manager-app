<?php

use Illuminate\Support\Facades\View;

it('resolves all payroll notification markdown views', function () {
    expect(View::exists('payroll::emails.notifications.pay-run-approved'))->toBeTrue();
    expect(View::exists('payroll::emails.notifications.pay-run-finalized'))->toBeTrue();
    expect(View::exists('payroll::emails.notifications.pay-run-voided'))->toBeTrue();
    expect(View::exists('payroll::emails.notifications.pay-stub-available'))->toBeTrue();
});
