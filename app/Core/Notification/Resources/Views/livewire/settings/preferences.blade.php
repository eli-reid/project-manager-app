<section class="w-full">
    <livewire:settings::settings-heading />

    <flux:heading class="sr-only">{{ __('Notification settings') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Control how each notification type is delivered to you')">
        @if ($errors->any())
            <flux:callout variant="danger" icon="x-circle" heading="{{ $errors->first() }}" class="my-4" />
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
            class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <flux:heading size="sm">{{ __('Push notifications for this device') }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                        {{ __('Turn browser push alerts on or off for this signed-in device.') }}
                    </flux:text>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400" x-text="supported ? (enabled ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}') : '{{ __('Not supported in this browser') }}'"></flux:text>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button type="button" size="sm" variant="primary" x-bind:disabled="!supported || enabled || loading" x-on:click="enable()">
                        {{ __('Enable') }}
                    </flux:button>
                    <flux:button type="button" size="sm" variant="filled" x-bind:disabled="!supported || !enabled || loading" x-on:click="disable()">
                        {{ __('Disable') }}
                    </flux:button>
                </div>
            </div>
        </div>

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