<?php

namespace App\Domains\Clients\Livewire\Admin\Clients;

use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.client-management-admin')]
#[Title('Clients')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    public function deleteClient(string $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);
        $this->authorize('delete', $client);

        $client->delete();

        session()->flash('success', 'Client deleted successfully.');
    }

    public function render()
    {
        return view('clients::livewire.admin.clients.index', [
            'clients' => Client::query()->latest()->paginate(10),
        ]);
    }
}
