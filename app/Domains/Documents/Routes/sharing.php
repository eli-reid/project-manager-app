<?php

use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')
    ->name('documents.')
    ->middleware('auth')
    ->group(function (): void {
        // Share management API endpoints
        Route::prefix('{document}/shares')
            ->name('shares.')
            ->middleware('can:share,document')
            ->group(function (): void {
                Route::get('{share}/toggle', function (Document $document, DocumentShare $share) {
                    if ($share->document_id !== $document->id) {
                        abort(404);
                    }

                    $isActive = ! $share->is_active;
                    $share->update(['is_active' => $isActive]);

                    return redirect()->back()->with('success', 'Share '.($isActive ? 'enabled' : 'disabled').'.');
                })->name('toggle');

                Route::delete('{share}', function (Document $document, DocumentShare $share) {
                    if ($share->document_id !== $document->id) {
                        abort(404);
                    }

                    $share->forceDelete();

                    return redirect()->back()->with('success', 'Share deleted.');
                })->name('destroy');
            });
    });
