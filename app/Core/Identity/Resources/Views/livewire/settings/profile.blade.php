<section class="w-full">
    <livewire:settings::settings-heading />

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="first_name" :label="__('First Name')" type="text" required autofocus autocomplete="given-name" />

            <flux:input wire:model="last_name" :label="__('Last Name')" type="text" required autocomplete="family-name" />

            <flux:input wire:model="phone" :label="__('Phone')" type="tel" autocomplete="tel" />

            <flux:input wire:model="username" :label="__('Username')" type="text" required autocomplete="username" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 text-green-600!">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">My Addresses</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Manage addresses attached to your profile.</p>
                    </div>
                    <flux:button type="button" wire:click="addProfileAddressRow" variant="ghost" size="sm">Add Address</flux:button>
                </div>

                @foreach ($profile_addresses as $index => $address)
                    <div wire:key="profile-address-{{ $index }}" class="space-y-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <flux:input wire:model="profile_addresses.{{ $index }}.address1" :label="__('Address 1')" type="text" />
                                @error('profile_addresses.'.$index.'.address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <flux:input wire:model="profile_addresses.{{ $index }}.address2" :label="__('Address 2')" type="text" />
                                @error('profile_addresses.'.$index.'.address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <flux:input wire:model="profile_addresses.{{ $index }}.city" :label="__('City')" type="text" />
                                @error('profile_addresses.'.$index.'.city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <flux:input wire:model="profile_addresses.{{ $index }}.state" :label="__('State')" type="text" />
                                @error('profile_addresses.'.$index.'.state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <flux:input wire:model="profile_addresses.{{ $index }}.zip" :label="__('Zip')" type="text" />
                                @error('profile_addresses.'.$index.'.zip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <flux:input wire:model="profile_addresses.{{ $index }}.country" :label="__('Country')" type="text" />
                                @error('profile_addresses.'.$index.'.country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <flux:button type="button" wire:click="removeProfileAddressRow({{ $index }})" variant="danger" size="sm">Remove</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

    </x-settings.layout>
</section>
