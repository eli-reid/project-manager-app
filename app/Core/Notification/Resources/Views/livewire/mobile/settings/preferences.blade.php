<section class="space-y-4 px-4 py-4">
    <livewire:settings::mobile.settings-tabs />

    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Notifications') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Control how each notification type is delivered to you') }}</flux:text>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="x-circle" heading="{{ $errors->first() }}" />
    @endif

    <div
        x-data="{
            enabled: false,
            loading: false,
            supported: ('serviceWorker' in navigator) && ('PushManager' in window),
            async init() {
                if (!this.supported) {
                    return;
                }

                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    this.enabled = !!subscription;
                } catch {
                    this.enabled = false;
                }

                window.addEventListener('push-subscription-changed', (event) => {
                    this.enabled = !!event.detail?.subscribed;
                    this.loading = false;
                });
            },
            async enable() {
                this.loading = true;
                const ok = await window.enablePushNotifications?.();

                if (!ok) {
                    this.loading = false;
                }
            },
            async disable() {
                this.loading = true;
                const ok = await window.disablePushNotifications?.();

                if (!ok) {
                    this.loading = false;
                }
            }
        }"
        class="space-y-3 rounded-2xl border border-zinc-800 bg-zinc-900 p-4"
    >
        <div class="space-y-1">
            <flux:heading size="sm">{{ __('Push notifications for this device') }}</flux:heading>
            <flux:text size="sm" class="text-zinc-400">{{ __('Turn browser push alerts on or off for this signed-in device.') }}</flux:text>
            <flux:text size="sm" class="text-zinc-500" x-text="supported ? (enabled ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}') : '{{ __('Not supported in this browser') }}'"></flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button type="button" size="sm" variant="primary" x-bind:disabled="!supported || enabled || loading" x-on:click="enable()" class="flex-1">
                {{ __('Enable') }}
            </flux:button>
            <flux:button type="button" size="sm" variant="filled" x-bind:disabled="!supported || !enabled || loading" x-on:click="disable()" class="flex-1">
                {{ __('Disable') }}
            </flux:button>
        </div>
    </div>

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
