<section class="space-y-4 px-4 py-4">
    <livewire:settings::mobile.settings-tabs />

    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Profile') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Update your profile and email address') }}</flux:text>
    </div>

    <form wire:submit="updateProfileInformation" class="space-y-5 rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
        <flux:input wire:model="first_name" :label="__('First Name')" type="text" required autofocus autocomplete="given-name" />

        <flux:input wire:model="last_name" :label="__('Last Name')" type="text" required autocomplete="family-name" />

        <flux:input wire:model="phone" :label="__('Phone')" type="tel" autocomplete="tel" />

        <flux:input wire:model="username" :label="__('Username')" type="text" required autocomplete="username" />

        <div>
            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            @if ($this->hasUnverifiedEmail)
                <div>
                    <flux:text class="mt-3 text-zinc-400">
                        {{ __('Your email address is unverified.') }}

                        <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>

            <x-action-message class="text-xs" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

</section>
