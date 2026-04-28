<?php

use App\Domains\Documents\Livewire\User\Documents\GlobalIndex;
use App\Domains\Documents\Livewire\User\Documents\Index;
use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('documents')
    ->name('documents.')
    ->group(function (): void {
        Route::livewire('/', Index::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('index');

        Route::livewire('/global', GlobalIndex::class)
            ->middleware('can:viewAny,'.Document::class)
            ->name('global');

        Route::get('/{document}/download', function (Document $document) {
            return Storage::disk($document->storage_disk)->download($document->storage_path, $document->original_name);
        })
            ->middleware('can:view,document')
            ->name('download');

        Route::get('/{document}/view', function (Document $document) {
            return Storage::disk($document->storage_disk)->response(
                $document->storage_path,
                $document->original_name,
                [
                    'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
                ]
            );
        })
            ->middleware('can:view,document')
            ->name('view');
    });
