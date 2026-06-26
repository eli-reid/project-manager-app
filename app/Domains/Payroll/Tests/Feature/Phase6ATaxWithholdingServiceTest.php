<?php

use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Services\TaxWithholdingService;

it('uses default withholding tables and rates when no overrides exist', function (): void {
    Settings::set('payroll.tax_withholding.federal_table', json_encode([
        ['up_to' => null, 'rate' => 0.12],
    ]));
    Settings::set('payroll.tax_withholding.state_table', json_encode([
        ['up_to' => null, 'rate' => 0.05],
    ]));
    Settings::set('payroll.tax_withholding.local_table', json_encode([
        ['up_to' => null, 'rate' => 0.01],
    ]));
    Settings::set('payroll.tax_withholding.social_security_rate', '0.062');
    Settings::set('payroll.tax_withholding.medicare_rate', '0.0145');

    $service = app(TaxWithholdingService::class);

    $taxes = $service->calculate(1000.00);

    expect($taxes['federal'])->toBe(120.00)
        ->and($taxes['state'])->toBe(50.00)
        ->and($taxes['local'])->toBe(10.00)
        ->and($taxes['social_security'])->toBe(62.00)
        ->and($taxes['medicare'])->toBe(14.50)
        ->and($taxes['total'])->toBe(256.50);
});

it('uses configurable progressive withholding tables when configured', function (): void {
    Settings::set('payroll.tax_withholding.federal_table', json_encode([
        ['up_to' => 500.0, 'rate' => 0.10],
        ['up_to' => 1000.0, 'rate' => 0.20],
        ['up_to' => null, 'rate' => 0.30],
    ]));

    Settings::set('payroll.tax_withholding.state_table', json_encode([
        ['up_to' => null, 'rate' => 0.04],
    ]));

    Settings::set('payroll.tax_withholding.local_table', json_encode([
        ['up_to' => null, 'rate' => 0.015],
    ]));

    Settings::set('payroll.tax_withholding.social_security_rate', '6.2');
    Settings::set('payroll.tax_withholding.medicare_rate', '1.45');

    $service = app(TaxWithholdingService::class);

    $taxes = $service->calculate(1500.00);

    expect($taxes['federal'])->toBe(300.00)
        ->and($taxes['state'])->toBe(60.00)
        ->and($taxes['local'])->toBe(22.50)
        ->and($taxes['social_security'])->toBe(93.00)
        ->and($taxes['medicare'])->toBe(21.75)
        ->and($taxes['total'])->toBe(497.25);
});

it('falls back to defaults when configured table data is invalid', function (): void {
    Settings::set('payroll.tax_withholding.state_table', json_encode([
        ['up_to' => null, 'rate' => 0.05],
    ]));

    Settings::set('payroll.tax_withholding.federal_table', '{not-valid-json}');

    $service = app(TaxWithholdingService::class);

    $taxes = $service->calculate(1000.00);

    expect($taxes['federal'])->toBe(120.00);
});
