<?php

use App\Domains\Documents\Models\DocumentShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('share')
    ->name('share.')
    ->group(function (): void {
        Route::get('{token}', function (string $token) {
            $share = DocumentShare::where('share_token', $token)->firstOrFail();

            if (! $share->isValid()) {
                abort(403, $share->getExpirationReason());
            }

            return view('documents::shared', [
                'share' => $share->load('document', 'createdBy'),
            ]);
        })->name('view');

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

            $share->recordDownload();

            return response()->download(
                storage_path('app/'.$document->storage_path),
                $document->original_name
            );
        })->name('download');
    });
