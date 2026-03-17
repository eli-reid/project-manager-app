<?php

namespace App\Domains\Clients\Livewire\Admin\Clients;

use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Client Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Client $client = null;

    public bool $isEdit = false;

    public string $company_name = '';

    public ?string $contact_name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $mobile = null;

    public ?string $notes = null;

    public bool $is_active = true;

    public function mount(?Client $client = null): void
    {
        if ($client !== null && $client->exists) {
            $this->authorize('update', $client);

            $this->client = $client;
            $this->isEdit = true;
            $this->company_name = $client->company_name;
            $this->contact_name = $client->contact_name;
            $this->email = $client->email;
            $this->phone = $client->phone;
            $this->mobile = $client->mobile;
            $this->notes = $client->notes;
            $this->is_active = (bool) $client->is_active;

            return;
        }

        $this->authorize('create', Client::class);
    }

    protected function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            $client = $this->client;
            if ($client === null) {
                return;
            }

            $this->authorize('update', $client);
            $client->update($validated);

            session()->flash('success', 'Client updated successfully.');
        } else {
            $this->authorize('create', Client::class);
            Client::query()->create($validated);

            session()->flash('success', 'Client created successfully.');
        }

        $this->redirectRoute('admin.clients.index', navigate: true);
    }

    public function render()
    {
        return view('clients::livewire.admin.clients.form');
    }
}
