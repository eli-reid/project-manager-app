<?php

namespace App\Domains\Addresses\Livewire\Admin\Addresses;

use App\Domains\Addresses\Models\Address;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('clients::layouts.client-management-admin')]
#[Title('Addresses')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Address::class);
    }

    public function deleteAddress(string $addressId): void
    {
        $address = Address::query()->findOrFail($addressId);
        $this->authorize('delete', $address);

        $address->delete();

        session()->flash('success', 'Address deleted successfully.');
    }

    public function render()
    {
        return view('addresses::livewire.admin.addresses.index', [
            'addresses' => Address::query()
                ->with('client:id,company_name')
                ->latest()
                ->paginate(10),
        ]);
    }
}
