<?php

use App\Livewire\Layouts\AccessAdmin;
use App\Livewire\Layouts\ClientManagementAdmin;
use App\Livewire\Layouts\PayrollAdmin;
use App\Livewire\Layouts\SettingsAdmin;
use App\Livewire\Layouts\StockInvoicesAdmin;
use App\Livewire\Layouts\TimeManagementAdmin;
use Livewire\Component;

it('defines dedicated livewire classes for domain admin layouts', function (): void {
    expect(is_subclass_of(AccessAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(SettingsAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(ClientManagementAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(TimeManagementAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(StockInvoicesAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(PayrollAdmin::class, Component::class))->toBeTrue();
});

it('maps each domain layout class to the correct layout view', function (): void {
    $accessAdminSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/AccessAdmin.php');
    $settingsAdminSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/SettingsAdmin.php');
    $clientManagementSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/ClientManagementAdmin.php');
    $timeManagementSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/TimeManagementAdmin.php');
    $stockInvoicesSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/StockInvoicesAdmin.php');
    $payrollSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/PayrollAdmin.php');

    expect($accessAdminSource)->toContain("return view('core-user::layouts.access-admin');");
    expect($settingsAdminSource)->toContain("return view('core::layouts.settings-admin');");
    expect($clientManagementSource)->toContain("return view('clients::layouts.client-management-admin');");
    expect($timeManagementSource)->toContain("return view('timecards::layouts.time-management-admin');");
    expect($stockInvoicesSource)->toContain("return view('stock::layouts.stock-invoices-admin');");
    expect($payrollSource)->toContain("return view('payroll::layouts.payroll-admin');");
});
