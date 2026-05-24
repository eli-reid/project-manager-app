<section class="space-y-4 px-4 py-4">
    <livewire:settings::mobile.settings-tabs />

    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Notifications') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Control how each notification type is delivered to you') }}</flux:text>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="x-circle" heading="{{ $errors->first() }}" />
    @endif

    <form wire:submit="save" class="space-y-4">
        <div class="space-y-4">
            @foreach ($definitions as $definition)
                <div wire:key="notification-{{ $definition['key'] }}" class="space-y-3 rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                    <div class="space-y-1">
                        <flux:heading size="sm">{{ $definition['label'] }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-400">{{ $definition['description'] }}</flux:text>
                    </div>

                    <div class="space-y-2">
                        @foreach ($definition['channels'] as $channel)
                            <div wire:key="notification-{{ $definition['key'] }}-channel-{{ $channel['key'] }}" class="rounded-xl border border-zinc-800 px-3 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <flux:text class="font-medium">{{ $channel['label'] }}</flux:text>
                                        <flux:text size="sm" class="text-zinc-500">
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

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>

            <x-action-message class="text-xs" on="preferences-saved">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
