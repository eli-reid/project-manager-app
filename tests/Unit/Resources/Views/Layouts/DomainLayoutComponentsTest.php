<?php

use App\Core\Identity\Livewire\Layouts\AccessAdmin;
use App\Core\Settings\Livewire\Layouts\SettingsAdmin;
use App\Domains\Clients\Livewire\Layouts\ClientManagementAdmin;
use App\Domains\Payroll\Livewire\Layouts\PayrollAdmin;
use App\Domains\Stock\Livewire\Layouts\StockInvoicesAdmin;
use App\Livewire\Layouts\Contracts\ProvidesDomainNavbar;
use App\Livewire\Layouts\DomainLayout;
use App\Livewire\Layouts\DomainNavbar;
use Livewire\Component;

it('defines dedicated livewire classes for domain admin layouts', function (): void {
    expect(is_subclass_of(AccessAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(SettingsAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(ClientManagementAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(StockInvoicesAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(PayrollAdmin::class, Component::class))->toBeTrue();
    expect(is_subclass_of(DomainLayout::class, Component::class))->toBeTrue();
    expect(is_subclass_of(DomainNavbar::class, Component::class))->toBeTrue();

    expect(is_subclass_of(AccessAdmin::class, ProvidesDomainNavbar::class))->toBeTrue();
    expect(is_subclass_of(SettingsAdmin::class, ProvidesDomainNavbar::class))->toBeTrue();
    expect(is_subclass_of(ClientManagementAdmin::class, ProvidesDomainNavbar::class))->toBeTrue();
    expect(is_subclass_of(StockInvoicesAdmin::class, ProvidesDomainNavbar::class))->toBeTrue();
    expect(is_subclass_of(PayrollAdmin::class, ProvidesDomainNavbar::class))->toBeTrue();
});

it('maps each domain layout class to the correct layout view', function (): void {
    $accessAdminSource = file_get_contents(__DIR__.'/../../../../../app/Core/Identity/Livewire/Layouts/AccessAdmin.php');
    $settingsAdminSource = file_get_contents(__DIR__.'/../../../../../app/Core/Settings/Livewire/Layouts/SettingsAdmin.php');
    $clientManagementSource = file_get_contents(__DIR__.'/../../../../../app/Domains/Clients/Livewire/Layouts/ClientManagementAdmin.php');
    $stockInvoicesSource = file_get_contents(__DIR__.'/../../../../../app/Domains/Stock/Livewire/Layouts/StockInvoicesAdmin.php');
    $payrollSource = file_get_contents(__DIR__.'/../../../../../app/Domains/Payroll/Livewire/Layouts/PayrollAdmin.php');
    $domainLayoutSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/DomainLayout.php');
    $domainNavbarSource = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/DomainNavbar.php');

    expect($accessAdminSource)->toContain("return view('core-user::livewire.layouts.access-admin');");
    expect($accessAdminSource)->toContain('public static function navbarItems(): array');
    expect($settingsAdminSource)->toContain("return view('core::livewire.layouts.settings-admin');");
    expect($settingsAdminSource)->toContain('public static function navbarItems(): array');
    expect($clientManagementSource)->toContain("return view('clients::livewire.layouts.client-management-admin');");
    expect($clientManagementSource)->toContain('public static function navbarItems(): array');
    expect($stockInvoicesSource)->toContain("return view('stock::livewire.layouts.stock-invoices-admin');");
    expect($stockInvoicesSource)->toContain('public static function navbarItems(): array');
    expect($payrollSource)->toContain("return view('payroll::livewire.layouts.payroll-admin');");
    expect($payrollSource)->toContain('public static function navbarItems(): array');
    expect($domainLayoutSource)->toContain("return view('livewire.layouts.domain-layout');");
    expect($domainNavbarSource)->toContain("return view('livewire.layouts.domain-navbar');");
});
