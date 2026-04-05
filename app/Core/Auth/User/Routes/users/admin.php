<?php

use App\Core\Auth\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\Auth\User\Livewire\Admin\Users\Index as UserIndex;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', UserIndex::class)->name('index');
    Route::get('/create', UserForm::class)->name('create');
    Route::get('/{user}/edit', UserForm::class)->name('edit');
    Route::post('/{user}/generate-company-email', function (User $user, CpanelMailboxManager $mailboxManager): RedirectResponse {
        Gate::authorize('manage-email-accounts');

        $result = $mailboxManager->generateForUser($user);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    })->name('generate-company-email');
});
