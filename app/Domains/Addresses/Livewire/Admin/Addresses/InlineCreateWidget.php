<?php

namespace App\Domains\Addresses\Livewire\Admin\Addresses;

use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class InlineCreateWidget extends Component
{
    use AuthorizesRequests;

    public bool $showForm = false;

    public ?string $client_id = null;

    public string $address1 = '';

    public ?string $address2 = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $zip = null;

    public string $country = 'US';

    public function mount(?string $client_id = null): void
    {
        $this->client_id = $client_id;
    }

    #[On('client-inline-created')]
    public function setClientFromEvent(string $clientId): void
    {
        $this->client_id = $clientId;
    }

    public function open(): void
    {
        $this->authorize('create', Address::class);
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $preserveClientId = $this->client_id;

        $this->reset(['showForm', 'address1', 'address2', 'city', 'state', 'zip', 'country']);
        $this->country = 'US';
        $this->client_id = $preserveClientId;
    }

    public function saveInline(): void
    {
        $this->authorize('create', Address::class);

        $validated = $this->validate([
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:10'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);

        $address = Address::query()->create($validated);

        $label = trim(collect([$address->address1, $address->city, $address->state])->filter()->implode(', '));

        $this->dispatch('address-inline-created', addressId: (string) $address->id, label: $label);

        $this->cancel();
    }

    public function render()
    {
        return view('addresses::livewire.admin.addresses.inline-create-widget', [
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
        ]);
    }
}
