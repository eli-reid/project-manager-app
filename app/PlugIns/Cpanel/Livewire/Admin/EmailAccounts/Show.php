<?php

namespace App\PlugIns\Cpanel\Livewire\Admin\EmailAccounts;

use App\PlugIns\Cpanel\Models\CachedEmailAccount;
use App\PlugIns\Cpanel\Services\CpanelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core-user::livewire.layouts.access-admin')]
#[Title('Mailbox Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public CachedEmailAccount $cachedEmailAccount;

    public string $newPassword = '';

    public string $forwardTo = '';

    public string $autoresponderSubject = '';

    public string $autoresponderBody = '';

    public string $filterName = '';

    public string $filterFromContains = '';

    public string $filterForwardTo = '';

    public function mount(CachedEmailAccount $cachedEmailAccount): void
    {
        $this->authorize('manage-email-accounts');
        $this->cachedEmailAccount = $cachedEmailAccount;
    }

    public function generatePassword(): void
    {
        $this->newPassword = str()->password(24);
    }

    public function resetMailboxPassword(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'newPassword' => ['required', 'string', 'min:12'],
        ]);

        $result = $cpanelService->updateEmailPassword(
            email: $this->cachedEmailAccount->email,
            password: (string) $validated['newPassword'],
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'newPassword' => (string) ($result['message'] ?? 'Unable to reset mailbox password.'),
            ]);
        }

        session()->flash('success', 'Mailbox password updated successfully.');
    }

    public function launchWebmail(CpanelService $cpanelService)
    {
        $this->authorize('manage-email-accounts');

        $result = $cpanelService->createWebmailSession($this->cachedEmailAccount->email);

        if (! ($result['success'] ?? false)) {
            session()->flash('error', (string) ($result['message'] ?? 'Unable to launch webmail.'));

            return;
        }

        if (isset($result['login_url'], $result['session'])) {
            $this->dispatch('webmail-auto-login',
                loginUrl: $result['login_url'],
                session: $result['session'],
            );

            return;
        }

        $url = (string) ($result['url'] ?? '');

        if ($url === '') {
            session()->flash('error', 'Unable to launch webmail.');

            return;
        }

        return $this->redirect($url, navigate: false);
    }

    public function addForwarder(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'forwardTo' => ['required', 'email', 'max:255'],
        ]);

        $result = $cpanelService->addForwarder(
            email: $this->cachedEmailAccount->email,
            forwardTo: (string) $validated['forwardTo'],
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'forwardTo' => (string) ($result['message'] ?? 'Unable to add forwarder.'),
            ]);
        }

        $this->forwardTo = '';
        session()->flash('success', 'Forwarder added successfully.');
    }

    public function deleteForwarder(string $forwardTo, CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $result = $cpanelService->deleteForwarder(
            email: $this->cachedEmailAccount->email,
            forwardTo: $forwardTo,
        );

        if (! ($result['success'] ?? false)) {
            session()->flash('error', (string) ($result['message'] ?? 'Unable to delete forwarder.'));

            return;
        }

        session()->flash('success', 'Forwarder deleted successfully.');
    }

    public function addAutoresponder(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'autoresponderSubject' => ['required', 'string', 'max:255'],
            'autoresponderBody' => ['required', 'string', 'max:5000'],
        ]);

        $result = $cpanelService->createAutoresponder(
            email: $this->cachedEmailAccount->email,
            subject: (string) $validated['autoresponderSubject'],
            body: (string) $validated['autoresponderBody'],
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'autoresponderSubject' => (string) ($result['message'] ?? 'Unable to add autoresponder.'),
            ]);
        }

        $this->autoresponderSubject = '';
        $this->autoresponderBody = '';
        session()->flash('success', 'Autoresponder saved successfully.');
    }

    public function deleteAutoresponder(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $result = $cpanelService->deleteAutoresponder($this->cachedEmailAccount->email);

        if (! ($result['success'] ?? false)) {
            session()->flash('error', (string) ($result['message'] ?? 'Unable to delete autoresponder.'));

            return;
        }

        session()->flash('success', 'Autoresponder deleted successfully.');
    }

    public function addFilter(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'filterName' => ['required', 'string', 'max:255'],
            'filterFromContains' => ['required', 'string', 'max:255'],
            'filterForwardTo' => ['required', 'email', 'max:255'],
        ]);

        $result = $cpanelService->createEmailFilter(
            email: $this->cachedEmailAccount->email,
            filterName: (string) $validated['filterName'],
            fromContains: (string) $validated['filterFromContains'],
            forwardTo: (string) $validated['filterForwardTo'],
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'filterName' => (string) ($result['message'] ?? 'Unable to add filter.'),
            ]);
        }

        $this->filterName = '';
        $this->filterFromContains = '';
        $this->filterForwardTo = '';
        session()->flash('success', 'Filter added successfully.');
    }

    public function deleteFilter(string $filterName, CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $result = $cpanelService->deleteEmailFilter(
            email: $this->cachedEmailAccount->email,
            filterName: $filterName,
        );

        if (! ($result['success'] ?? false)) {
            session()->flash('error', (string) ($result['message'] ?? 'Unable to delete filter.'));

            return;
        }

        session()->flash('success', 'Filter deleted successfully.');
    }

    public function render(CpanelService $cpanelService)
    {
        $forwardersResult = $cpanelService->listForwarders($this->cachedEmailAccount->email);
        $autorespondersResult = $cpanelService->listAutoresponders($this->cachedEmailAccount->email);
        $filtersResult = $cpanelService->listEmailFilters($this->cachedEmailAccount->email);

        return view('cpanel::livewire.admin.email-accounts.show', [
            'forwarders' => $forwardersResult['forwarders'] ?? [],
            'forwardersError' => $forwardersResult['success'] ?? false
                ? null
                : (string) ($forwardersResult['message'] ?? 'Unable to load forwarders.'),
            'autoresponders' => $autorespondersResult['autoresponders'] ?? [],
            'autorespondersError' => $autorespondersResult['success'] ?? false
                ? null
                : (string) ($autorespondersResult['message'] ?? 'Unable to load autoresponders.'),
            'filters' => $filtersResult['filters'] ?? [],
            'filtersError' => $filtersResult['success'] ?? false
                ? null
                : (string) ($filtersResult['message'] ?? 'Unable to load filters.'),
        ]);
    }
}
