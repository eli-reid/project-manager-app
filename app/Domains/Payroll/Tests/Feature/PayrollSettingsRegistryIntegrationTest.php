<?php

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\Services\SettingsSqliteService;

it('registers payroll settings definitions in the settings registry', function (): void {
    /** @var SettingsRegistryContract $registry */
    $registry = app(SettingsRegistryContract::class);

    $definitions = $registry->definitionsByDomain();

    expect($definitions)->toHaveKey('payroll');

    $keys = collect($definitions['payroll'])->pluck('key');

    expect($keys)->toContain('payroll.reconciliation.enabled')
        ->toContain('payroll.reconciliation.hours_tolerance')
        ->toContain('payroll.reconciliation.require_project_match')
        ->toContain('payroll.reconciliation.require_cost_code_match')
        ->toContain('payroll.reconciliation.warning_only');
});

it('synchronizes payroll settings into the settings sqlite store', function (): void {
    /** @var SettingsSqliteService $settings */
    $settings = app(SettingsSqliteService::class);

    expect($settings->has('payroll.reconciliation.enabled'))->toBeTrue()
        ->and($settings->has('payroll.reconciliation.hours_tolerance'))->toBeTrue()
        ->and($settings->has('payroll.reconciliation.require_project_match'))->toBeTrue()
        ->and($settings->has('payroll.reconciliation.require_cost_code_match'))->toBeTrue()
        ->and($settings->has('payroll.reconciliation.warning_only'))->toBeTrue();
});
