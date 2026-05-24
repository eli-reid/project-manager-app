<section class="w-full">
    <livewire:settings::settings-heading />

    <flux:heading class="sr-only">{{ __('Notification settings') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Control how each notification type is delivered to you')">
        @if ($errors->any())
            <flux:callout variant="danger" icon="x-circle" heading="{{ $errors->first() }}" class="my-4" />
        @endif

        <form wire:submit="save" class="my-6 w-full space-y-6">
            <div class="space-y-4">
                @foreach ($definitions as $definition)
                    <div wire:key="notification-{{ $definition['key'] }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="space-y-1">
                            <flux:heading size="sm">{{ $definition['label'] }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">{{ $definition['description'] }}</flux:text>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($definition['channels'] as $channel)
                                <div wire:key="notification-{{ $definition['key'] }}-channel-{{ $channel['key'] }}" class="rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <flux:text class="font-medium">{{ $channel['label'] }}</flux:text>
                                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                                {{ $channel['supported'] ? __('Supported for this notification') : __('Not supported for this notification') }}
                                            </flux:text>
                                        </div>

                                        <flux:switch
                                            wire:model.live="preferences.{{ $definition['form_key'] }}.{{ $channel['key'] }}"
                                            :disabled="! $channel['supported']"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message on="preferences-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>