<?php

namespace App\Domains\Addresses\Livewire\Admin\Addresses;

use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use App\Domains\Addresses\Services\AddressAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('addresses::layouts.addresses-admin')]
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
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $query = app(AddressAccessService::class)
            ->accessibleAddressesQuery($user)
            ->with('client:id,company_name');

        return view('addresses::livewire.admin.addresses.index', [
            'addresses' => $query
                ->latest()
                ->paginate(10),
        ]);
    }
}
