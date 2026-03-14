<?php

namespace App\Domains\Clients\Livewire\Admin\Clients;

use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class InlineCreateWidget extends Component
{
    use AuthorizesRequests;

    public bool $showForm = false;

    public string $company_name = '';

    public ?string $contact_name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public function open(): void
    {
        $this->authorize('create', Client::class);
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'company_name', 'contact_name', 'email', 'phone']);
    }

    public function saveInline(): void
    {
        $this->authorize('create', Client::class);

        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $client = Client::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        $this->dispatch('client-inline-created', clientId: (string) $client->id, companyName: $client->company_name);

        $this->cancel();
    }

    public function render()
    {
        return view('clients::livewire.admin.clients.inline-create-widget');
    }
}
