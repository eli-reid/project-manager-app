<?php

use App\Domains\Invoices\Livewire\Admin\Invoices\Form as InvoiceForm;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index as InvoiceIndex;
use App\Domains\Invoices\Livewire\Admin\Invoices\PdfImport as InvoicePdfImport;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show as InvoiceShow;
use App\Domains\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::prefix('invoices')
    ->name('invoices.')
    ->middleware('can:viewAny,'.Invoice::class)
    ->group(function (): void {
        Route::livewire('/', InvoiceIndex::class)->name('index');

        Route::livewire('/create', InvoiceForm::class)
            ->middleware('can:create,'.Invoice::class)
            ->name('create');

        Route::livewire('/import', InvoicePdfImport::class)
            ->middleware('can:create,'.Invoice::class)
            ->name('import');

        Route::livewire('/{invoice}', InvoiceShow::class)
            ->middleware('can:view,invoice')
            ->name('show');

        Route::livewire('/{invoice}/edit', InvoiceForm::class)
            ->middleware('can:update,invoice')
            ->name('edit');
    });
