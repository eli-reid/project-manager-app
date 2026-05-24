<?php

use App\Domains\Documents\Livewire\PublicShares\Show as SharedDocumentShow;
use App\Domains\Documents\Models\DocumentShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('share')
    ->name('share.')
    ->group(function (): void {
        Route::livewire('{token}', SharedDocumentShow::class)->name('view');

        Route::post('{token}/verify-password', function (string $token, Request $request) {
            $share = DocumentShare::where('share_token', $token)->firstOrFail();

            if (! $share->requiresPassword()) {
                return redirect()->route('share.view', $token);
            }

            $request->validate([
                'password' => 'required|string',
            ]);

            if (! $share->verifyPassword($request->password)) {
                return redirect()->back()->withErrors(['password' => 'Invalid password.']);
            }

            session()->put("share.{$token}.verified", true);

            return redirect()->route('share.view', $token);
        })->name('verify-password');

        Route::get('{token}/download', function (string $token) {
            $share = DocumentShare::where('share_token', $token)->firstOrFail();

            if (! $share->isValid()) {
                abort(403, $share->getExpirationReason());
            }

            if ($share->requiresPassword() && ! session()->get("share.{$token}.verified")) {
                abort(403, 'Password verification required.');
            }

            $document = $share->document;

            if (! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
                abort(404, 'Shared file not found.');
            }

            $share->recordDownload();

            return Storage::disk($document->storage_disk)->download(
                $document->storage_path,
                $document->original_name
            );
        })->name('download');
    });
