<?php

namespace App\PlugIns\Cpanel\Http\Controllers\Admin;

use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GenerateCompanyEmailForUserController
{
    public function __invoke(User $user, CpanelMailboxManager $mailboxManager): RedirectResponse
    {
        Gate::authorize('manage-email-accounts');

        $result = $mailboxManager->generateForUser($user);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
