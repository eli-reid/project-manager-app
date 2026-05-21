<section class="space-y-4 px-4 py-4">
    @include('core-user::livewire.mobile.settings.tabs')

    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Update password') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Ensure your account is using a long, random password to stay secure') }}</flux:text>
    </div>

    <form method="POST" wire:submit="updatePassword" class="space-y-5 rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
        <flux:input
            wire:model="current_password"
            :label="__('Current password')"
            type="password"
            required
            autocomplete="current-password"
        />

        <flux:input
            wire:model="password"
            :label="__('New password')"
            type="password"
            required
            autocomplete="new-password"
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
        />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>

            <x-action-message class="text-xs" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
