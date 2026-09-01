<?php

namespace App\Core\Identity\Livewire\Settings;

use App\Core\Identity\Concerns\ProfileValidationRules;
use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $username = '';

    public string $email = '';

    /**
     * @var array<int, array{id:string|null,address1:string,address2:string,city:string,state:string,zip:string,country:string}>
     */
    public array $profile_addresses = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->phone = (string) ($user->phone ?? '');
        $this->username = $user->username;
        $this->email = $user->email;
        $this->profile_addresses = $user->addresses()
            ->orderBy('address1')
            ->get(['addresses.id', 'address1', 'address2', 'city', 'state', 'zip', 'country'])
            ->map(function (Address $address): array {
                return [
                    'id' => (string) $address->id,
                    'address1' => (string) $address->address1,
                    'address2' => (string) ($address->address2 ?? ''),
                    'city' => (string) ($address->city ?? ''),
                    'state' => (string) ($address->state ?? ''),
                    'zip' => (string) ($address->zip ?? ''),
                    'country' => (string) ($address->country ?? 'US'),
                ];
            })
            ->values()
            ->all();

        if ($this->profile_addresses === []) {
            $this->profile_addresses = [$this->emptyProfileAddressRow()];
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // Profile names are display-only in settings; they are managed outside this screen.
        $validated = $this->validate(Arr::except($this->profileRules($user->id), ['first_name', 'last_name']));
        $validated['phone'] = filled($validated['phone'] ?? null) ? $validated['phone'] : null;
        $addressesPayload = $this->normalizedProfileAddresses();
        $this->validateProfileAddresses($addressesPayload);

        DB::transaction(function () use ($user, $validated, $addressesPayload): void {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncProfileAddresses($user, $addressesPayload);
        });

        $this->dispatch('profile-updated', name: trim($user->first_name.' '.$user->last_name));
    }

    public function addProfileAddressRow(): void
    {
        $this->profile_addresses[] = $this->emptyProfileAddressRow();
    }

    public function removeProfileAddressRow(int $index): void
    {
        if (! isset($this->profile_addresses[$index])) {
            return;
        }

        unset($this->profile_addresses[$index]);
        $this->profile_addresses = array_values($this->profile_addresses);

        if ($this->profile_addresses === []) {
            $this->profile_addresses[] = $this->emptyProfileAddressRow();
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! (Auth::user() instanceof MustVerifyEmail)
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    /**
     * @return array{id:null,address1:string,address2:string,city:string,state:string,zip:string,country:string}
     */
    private function emptyProfileAddressRow(): array
    {
        return [
            'id' => null,
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => 'US',
        ];
    }

    /**
     * @return array<int, array{id:string|null,address1:string,address2:string,city:string,state:string,zip:string,country:string}>
     */
    private function normalizedProfileAddresses(): array
    {
        return collect($this->profile_addresses)
            ->map(function (array $row): array {
                return [
                    'id' => filled($row['id'] ?? null) ? (string) $row['id'] : null,
                    'address1' => trim((string) ($row['address1'] ?? '')),
                    'address2' => trim((string) ($row['address2'] ?? '')),
                    'city' => trim((string) ($row['city'] ?? '')),
                    'state' => trim((string) ($row['state'] ?? '')),
                    'zip' => trim((string) ($row['zip'] ?? '')),
                    'country' => strtoupper(trim((string) ($row['country'] ?? 'US'))),
                ];
            })
            ->filter(function (array $row): bool {
                return collect(Arr::except($row, ['id']))
                    ->contains(fn (string $value): bool => $value !== '');
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id:string|null,address1:string,address2:string,city:string,state:string,zip:string,country:string}>  $addressesPayload
     */
    private function validateProfileAddresses(array $addressesPayload): void
    {
        Validator::make([
            'profile_addresses' => $addressesPayload,
        ], [
            'profile_addresses' => ['array'],
            'profile_addresses.*.id' => ['nullable', 'string', 'exists:addresses,id'],
            'profile_addresses.*.address1' => ['required', 'string', 'max:255'],
            'profile_addresses.*.address2' => ['nullable', 'string', 'max:255'],
            'profile_addresses.*.city' => ['nullable', 'string', 'max:255'],
            'profile_addresses.*.state' => ['nullable', 'string', 'max:255'],
            'profile_addresses.*.zip' => ['nullable', 'string', 'max:50'],
            'profile_addresses.*.country' => ['required', 'string', 'max:10'],
        ])->validate();
    }

    /**
     * @param  array<int, array{id:string|null,address1:string,address2:string,city:string,state:string,zip:string,country:string}>  $addressesPayload
     */
    private function syncProfileAddresses(User $user, array $addressesPayload): void
    {
        $persistedAddressIds = [];

        foreach ($addressesPayload as $addressPayload) {
            $addressId = $addressPayload['id'];
            $payload = Arr::except($addressPayload, ['id', 'country']);
            $payload['country'] = $addressPayload['country'] !== '' ? $addressPayload['country'] : 'US';
            $payload['client_id'] = null;

            if ($addressId !== null) {
                $existingAddress = $user->addresses()->whereKey($addressId)->first();

                if ($existingAddress !== null) {
                    $existingAddress->update($payload);
                    $persistedAddressIds[] = (string) $existingAddress->id;

                    continue;
                }
            }

            $createdAddress = Address::query()->create($payload);
            $persistedAddressIds[] = (string) $createdAddress->id;
        }

        $user->addresses()->sync($persistedAddressIds);

        $this->profile_addresses = $user->addresses()
            ->orderBy('address1')
            ->get(['addresses.id', 'address1', 'address2', 'city', 'state', 'zip', 'country'])
            ->map(function (Address $address): array {
                return [
                    'id' => (string) $address->id,
                    'address1' => (string) $address->address1,
                    'address2' => (string) ($address->address2 ?? ''),
                    'city' => (string) ($address->city ?? ''),
                    'state' => (string) ($address->state ?? ''),
                    'zip' => (string) ($address->zip ?? ''),
                    'country' => (string) ($address->country ?? 'US'),
                ];
            })
            ->values()
            ->all();

        if ($this->profile_addresses === []) {
            $this->profile_addresses = [$this->emptyProfileAddressRow()];
        }
    }

    public function render()
    {
        $view = request()->routeIs('settings.mobile.*')
            ? view('core-user::livewire.mobile.settings.profile')
            : view('core-user::livewire.settings.profile');

        if (request()->routeIs('settings.mobile.*')) {
            return $view->layout('layouts.mobile', ['title' => __('Profile settings')]);
        }

        return $view;
    }
}
