<?php

it('includes stock orders, templates, and invoices in the stock and invoices admin navbar', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../app/Domains/Stock/Resources/Views/layouts/stock-invoices-admin.blade.php');

    expect($view)->toContain(":href=\"route('admin.stock-orders.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.stock-orders.*')\"");
    expect($view)->toContain("{{ __('Stock Orders') }}");

    expect($view)->toContain(":href=\"route('admin.stock-order-templates.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.stock-order-templates.*')\"");
    expect($view)->toContain("{{ __('Templates') }}");

    expect($view)->toContain(":href=\"route('admin.invoices.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.invoices.*')\"");
    expect($view)->toContain("{{ __('Invoices') }}");
});
