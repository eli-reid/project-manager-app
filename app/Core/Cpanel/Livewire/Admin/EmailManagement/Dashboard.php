<?php

namespace App\Core\Cpanel\Livewire\Admin\EmailManagement;

use App\Core\Cpanel\Jobs\SyncEmailAccountsJob;
use App\Core\Cpanel\Models\CachedEmailAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Email Management')]
class Dashboard extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('manage-email-accounts');
    }

    public function triggerSync(): void
    {
        $this->authorize('manage-email-accounts');

        SyncEmailAccountsJob::dispatch();

        session()->flash('success', 'Email account sync has been queued.');
    }

    public function render()
    {
        $lastSyncedAt = CachedEmailAccount::query()->max('last_synced_at');

        return view('cpanel::livewire.admin.email-management.dashboard', [
            'totalAccounts' => CachedEmailAccount::query()->count(),
            'suspendedAccounts' => CachedEmailAccount::query()->suspended()->count(),
            'highUsageAccounts' => CachedEmailAccount::query()->highUsage()->count(),
            'syncFailures' => CachedEmailAccount::query()->syncFailed()->count(),
            'lastSyncedAt' => $lastSyncedAt,
        ]);
    }
}
