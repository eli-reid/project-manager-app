<?php

namespace App\PlugIns\Cpanel\Livewire\Admin\EmailManagement;

use App\PlugIns\Cpanel\Services\CpanelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core-user::livewire.layouts.access-admin')]
#[Title('Domain Forwarders')]
class DomainForwarders extends Component
{
    use AuthorizesRequests;

    public string $sourceDomain = '';

    public string $destinationDomain = '';

    public function mount(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $this->sourceDomain = (string) $cpanelService->configuration()->domain;
    }

    public function addDomainForwarder(CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $validated = $this->validate([
            'sourceDomain' => ['required', 'string', 'max:255'],
            'destinationDomain' => ['required', 'string', 'max:255'],
        ]);

        $result = $cpanelService->addDomainForwarder(
            domain: (string) $validated['sourceDomain'],
            destinationDomain: (string) $validated['destinationDomain'],
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'sourceDomain' => (string) ($result['message'] ?? 'Unable to add domain forwarder.'),
            ]);
        }

        $this->destinationDomain = '';
        session()->flash('success', 'Domain forwarder added successfully.');
    }

    public function deleteDomainForwarder(string $domain, CpanelService $cpanelService): void
    {
        $this->authorize('manage-email-accounts');

        $result = $cpanelService->deleteDomainForwarder($domain);

        if (! ($result['success'] ?? false)) {
            session()->flash('error', (string) ($result['message'] ?? 'Unable to delete domain forwarder.'));

            return;
        }

        session()->flash('success', 'Domain forwarder deleted successfully.');
    }

    public function render(CpanelService $cpanelService)
    {
        $forwardersResult = $cpanelService->listDomainForwarders();

        return view('cpanel::livewire.admin.email-management.domain-forwarders', [
            'domainForwarders' => $forwardersResult['forwarders'] ?? [],
            'domainForwardersError' => $forwardersResult['success'] ?? false
                ? null
                : (string) ($forwardersResult['message'] ?? 'Unable to load domain forwarders.'),
        ]);
    }
}
