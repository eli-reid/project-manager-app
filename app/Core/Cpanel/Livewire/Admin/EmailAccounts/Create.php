<?php

namespace App\Core\Cpanel\Livewire\Admin\EmailAccounts;

use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\User\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Create Email Account')]
class Create extends Component
{
    use AuthorizesRequests;

    public ?string $selectedUserId = null;

    public string $username = '';

    public string $password = '';

    public ?int $quota = null;

    public function mount(): void
    {
        $this->authorize('manage-email-accounts');
        $this->quota = (int) config('services.cpanel.default_email_quota', 250);
    }

    public function updatedSelectedUserId(?string $selectedUserId): void
    {
        if ($selectedUserId === null || trim($selectedUserId) === '') {
            return;
        }

        $user = User::query()->find($selectedUserId);
        if ($user !== null) {
            $this->username = (string) $user->username;
        }
    }

    public function generatePassword(): void
    {
        $this->password = str()->password(24);
    }

    public function createMailbox(CpanelService $cpanelService, CpanelMailboxManager $mailboxManager): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'selectedUserId' => ['nullable', Rule::exists('users', 'id')],
            'username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],
            'password' => ['required', 'string', 'min:12'],
            'quota' => ['nullable', 'integer', 'min:0'],
        ], [
            'username.regex' => 'Username may only contain letters, numbers, dots, underscores, and dashes.',
        ]);

        $user = null;
        if ($validated['selectedUserId'] !== null) {
            $user = User::query()->find($validated['selectedUserId']);
        }

        if ($user !== null && trim((string) $user->username) === '') {
            throw ValidationException::withMessages([
                'selectedUserId' => 'Selected user does not have a username.',
            ]);
        }

        $username = strtolower(trim((string) $validated['username']));
        if ($user !== null) {
            $username = strtolower(trim((string) $user->username));
        }

        $result = $cpanelService->createEmailAccount(
            emailUsername: $username,
            password: (string) $validated['password'],
            quota: $validated['quota']
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'username' => (string) ($result['message'] ?? 'Unable to create mailbox.'),
            ]);
        }

        $email = (string) ($result['email'] ?? ($username.'@'.(string) config('services.cpanel.domain')));

        if ($user !== null) {
            $resolvedCompanyEmail = $mailboxManager->resolveCompanyEmail($username);
            if ($resolvedCompanyEmail !== null) {
                $user->forceFill(['company_email' => $resolvedCompanyEmail])->save();
                $email = $resolvedCompanyEmail;
            }
        }

        CachedEmailAccount::query()->updateOrCreate(
            ['email' => $email],
            [
                'domain' => (string) str($email)->after('@'),
                'suspended' => false,
                'quota' => (int) ($validated['quota'] ?? config('services.cpanel.default_email_quota', 250)),
                'usage' => 0,
                'usage_percentage' => 0,
                'raw_data' => [],
                'user_id' => $user?->id,
                'last_synced_at' => now(),
                'sync_failed' => false,
                'sync_error' => null,
            ]
        );

        session()->flash('success', 'Mailbox created for '.$email.'.');

        $this->selectedUserId = null;
        $this->username = '';
        $this->password = '';
        $this->quota = (int) config('services.cpanel.default_email_quota', 250);
    }

    public function render()
    {
        return view('cpanel::livewire.admin.email-accounts.create', [
            'users' => User::query()
                ->whereNotNull('username')
                ->where('username', '!=', '')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(150)
                ->get(['id', 'first_name', 'last_name', 'username', 'company_email']),
        ]);
    }
}
