<?php

namespace App\Core\Cpanel\Livewire\Admin\EmailAccounts;

use App\Core\Cpanel\Jobs\SyncEmailAccountsJob;
use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('core-user::layouts.access-admin')]
#[Title('Email Accounts')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = 'all';

    /**
     * @var array<int, string>
     */
    public array $selectedAccountIds = [];

    public string $bulkPassword = '';

    public function mount(): void
    {
        $this->authorize('manage-email-accounts');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->selectedAccountIds = [];
    }

    public function triggerSync(): void
    {
        $this->authorize('manage-email-accounts');

        if (app()->environment('local')) {
            Log::info('Email account index requested immediate local cPanel sync.');
            SyncEmailAccountsJob::dispatchSync();

            session()->flash('success', 'Email account sync completed.');

            return;
        }

        SyncEmailAccountsJob::dispatch();

        session()->flash('success', 'Email account sync has been queued.');
    }

    public function suspend(string $cachedEmailAccountId, CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $cachedAccount = CachedEmailAccount::query()->findOrFail($cachedEmailAccountId);
        $result = $cpanelService->suspendEmailAccount($cachedAccount->email);

        if ($result['success'] ?? false) {
            $cachedAccount->forceFill(['suspended' => true])->save();
            session()->flash('success', 'Email account suspended.');

            return;
        }

        session()->flash('error', (string) ($result['message'] ?? 'Unable to suspend email account.'));
    }

    public function unsuspend(string $cachedEmailAccountId, CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $cachedAccount = CachedEmailAccount::query()->findOrFail($cachedEmailAccountId);
        $result = $cpanelService->unsuspendEmailAccount($cachedAccount->email);

        if ($result['success'] ?? false) {
            $cachedAccount->forceFill(['suspended' => false])->save();
            session()->flash('success', 'Email account unsuspended.');

            return;
        }

        session()->flash('error', (string) ($result['message'] ?? 'Unable to unsuspend email account.'));
    }

    public function toggleAccountSelection(string $cachedEmailAccountId): void
    {
        if (in_array($cachedEmailAccountId, $this->selectedAccountIds, true)) {
            $this->selectedAccountIds = array_values(array_filter(
                $this->selectedAccountIds,
                fn (string $id): bool => $id !== $cachedEmailAccountId,
            ));

            return;
        }

        $this->selectedAccountIds[] = $cachedEmailAccountId;
    }

    public function clearSelection(): void
    {
        $this->selectedAccountIds = [];
    }

    public function bulkSuspend(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        [$successCount, $failureCount] = $this->processBulkStateChange(
            $cpanelService,
            shouldSuspend: true,
        );

        if ($successCount > 0) {
            session()->flash('success', "{$successCount} mailbox(es) suspended.");
        }

        if ($failureCount > 0) {
            session()->flash('error', "{$failureCount} mailbox(es) could not be suspended.");
        }
    }

    public function bulkUnsuspend(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        [$successCount, $failureCount] = $this->processBulkStateChange(
            $cpanelService,
            shouldSuspend: false,
        );

        if ($successCount > 0) {
            session()->flash('success', "{$successCount} mailbox(es) unsuspended.");
        }

        if ($failureCount > 0) {
            session()->flash('error', "{$failureCount} mailbox(es) could not be unsuspended.");
        }
    }

    public function bulkResetPassword(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        if ($this->selectedAccountIds === []) {
            session()->flash('error', 'Select at least one mailbox first.');

            return;
        }

        $validated = $this->validate([
            'bulkPassword' => ['required', 'string', 'min:12'],
        ]);

        $accounts = CachedEmailAccount::query()
            ->whereKey($this->selectedAccountIds)
            ->get(['id', 'email']);

        if ($accounts->isEmpty()) {
            session()->flash('error', 'No selected mailboxes were found.');

            return;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($accounts as $account) {
            $result = $cpanelService->updateEmailPassword(
                email: $account->email,
                password: (string) $validated['bulkPassword'],
            );

            if ($result['success'] ?? false) {
                $successCount++;

                continue;
            }

            $failureCount++;
        }

        if ($successCount > 0) {
            $this->bulkPassword = '';
            $this->selectedAccountIds = [];
            session()->flash('success', "Password reset completed for {$successCount} mailbox(es).");
        }

        if ($failureCount > 0) {
            throw ValidationException::withMessages([
                'bulkPassword' => "{$failureCount} mailbox(es) failed password reset.",
            ]);
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function processBulkStateChange(CpanelService $cpanelService, bool $shouldSuspend): array
    {
        if ($this->selectedAccountIds === []) {
            session()->flash('error', 'Select at least one mailbox first.');

            return [0, 0];
        }

        $accounts = CachedEmailAccount::query()
            ->whereKey($this->selectedAccountIds)
            ->get(['id', 'email', 'suspended']);

        if ($accounts->isEmpty()) {
            session()->flash('error', 'No selected mailboxes were found.');

            return [0, 0];
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($accounts as $account) {
            $result = $shouldSuspend
                ? $cpanelService->suspendEmailAccount($account->email)
                : $cpanelService->unsuspendEmailAccount($account->email);

            if ($result['success'] ?? false) {
                $account->forceFill(['suspended' => $shouldSuspend])->save();
                $successCount++;

                continue;
            }

            $failureCount++;
        }

        if ($successCount > 0) {
            $this->selectedAccountIds = [];
        }

        return [$successCount, $failureCount];
    }

    public function render()
    {
        $accounts = CachedEmailAccount::query()
            ->with('user:id,first_name,last_name')
            ->when($this->search !== '', function ($query): void {
                $query->where('email', 'like', '%'.$this->search.'%');
            })
            ->when($this->status !== 'all', function ($query): void {
                if ($this->status === 'active') {
                    $query->active();
                }

                if ($this->status === 'suspended') {
                    $query->suspended();
                }

                if ($this->status === 'high-usage') {
                    $query->highUsage();
                }
            })
            ->latest('last_synced_at')
            ->paginate(20);

        return view('cpanel::livewire.admin.email-accounts.index', [
            'accounts' => $accounts,
        ]);
    }
}
