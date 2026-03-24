<?php

use App\Domains\Invoices\Livewire\Admin\Invoices\Form as InvoiceForm;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index as InvoiceIndex;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show as InvoiceShow;
use App\Domains\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::prefix('invoices')
    ->name('invoices.')
    ->middleware('can:viewAny,'.Invoice::class)
    ->group(function (): void {
        Route::get('/', InvoiceIndex::class)->name('index');

        Route::get('/create', InvoiceForm::class)
            ->middleware('can:create,'.Invoice::class)
            ->name('create');

        Route::get('/{invoice}', InvoiceShow::class)
            ->middleware('can:view,invoice')
            ->name('show');

        Route::get('/{invoice}/edit', InvoiceForm::class)
            ->middleware('can:update,invoice')
            ->name('edit');
    });
