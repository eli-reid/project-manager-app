<?php

use App\Domains\Invoices\Livewire\Admin\Invoices\Form as InvoiceForm;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index as InvoiceIndex;
use App\Domains\Invoices\Livewire\Admin\Invoices\PdfImport as InvoicePdfImport;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show as InvoiceShow;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoicePdfImport as InvoicePdfImportModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

        // Streams a staged import PDF so reviewers can verify parsed values.
        // Scoped to the uploader so one user cannot read another's staged file.
        Route::get('/import/{invoicePdfImport}/preview', function (InvoicePdfImportModel $invoicePdfImport) {
            abort_unless($invoicePdfImport->created_by === Auth::id(), 403);

            $disk = Storage::disk('local');

            abort_unless(
                filled($invoicePdfImport->file_path) && $disk->exists($invoicePdfImport->file_path),
                404,
                'File not found.'
            );

            return response()->file(
                $disk->path($invoicePdfImport->file_path),
                ['Content-Type' => 'application/pdf']
            );
        })
            ->middleware('can:create,'.Invoice::class)
            ->name('import.preview');

        Route::livewire('/{invoice}', InvoiceShow::class)
            ->middleware('can:view,invoice')
            ->name('show');

        Route::livewire('/{invoice}/edit', InvoiceForm::class)
            ->middleware('can:update,invoice')
            ->name('edit');
    });
