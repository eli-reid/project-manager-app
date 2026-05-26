<?php

namespace App\Domains\Addresses\Livewire\Admin\Addresses;

use App\Domains\Addresses\Models\Address;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('addresses::layouts.addresses-admin')]
#[Title('Address Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Address $address = null;

    public bool $isEdit = false;

    public string $address1 = '';

    public ?string $address2 = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $zip = null;

    public string $country = 'US';

    public function mount(?Address $address = null): void
    {
        if ($address !== null && $address->exists) {
            $this->authorize('update', $address);

            $this->address = $address;
            $this->isEdit = true;
            $this->address1 = $address->address1;
            $this->address2 = $address->address2;
            $this->city = $address->city;
            $this->state = $address->state;
            $this->zip = $address->zip;
            $this->country = $address->country;

            return;
        }

        $this->authorize('create', Address::class);
    }

    protected function rules(): array
    {
        return [
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:10'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            $address = $this->address;
            if ($address === null) {
                return;
            }

            $this->authorize('update', $address);
            $address->update($validated);

            session()->flash('success', 'Address updated successfully.');
        } else {
            $this->authorize('create', Address::class);
            Address::query()->create($validated);

            session()->flash('success', 'Address created successfully.');
        }

        $this->redirectRoute('admin.addresses.index', navigate: true);
    }

    public function render()
    {
        return view('addresses::livewire.admin.addresses.form');
    }
}
