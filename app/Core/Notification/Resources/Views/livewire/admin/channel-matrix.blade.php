<section class="space-y-4">
    <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-950">
        <div class="flex items-start justify-between gap-4 max-md:flex-col">
            <div>
                <flux:heading size="lg">{{ __('Enable Notifications') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('Master switch for all notification delivery across the application.') }}
                </flux:text>
            </div>

            <flux:switch wire:model.live="notificationsEnabled" />
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-950">
        <div class="space-y-3">
            <div>
                <flux:heading size="lg">{{ __('Default Notification Channels') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('Choose the channels enabled by default before users customize their own preferences.') }}
                </flux:text>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (['database' => 'In-app', 'mail' => 'Email', 'sms' => 'SMS', 'push' => 'Push'] as $channelKey => $channelLabel)
                    <div class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-800">
                        <flux:text class="font-medium">{{ __($channelLabel) }}</flux:text>
                        <flux:switch wire:model.live="defaultChannels.{{ $channelKey }}" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex items-start justify-between gap-4 max-md:flex-col">
        <div>
            <flux:heading size="lg">{{ __('Notification Channel Rules') }}</flux:heading>
            <flux:text class="mt-1 max-w-3xl text-zinc-600 dark:text-zinc-400">
                {{ __('Admins choose which delivery channels each notification type is allowed to use. Users can only manage their own preferences within these rules.') }}
            </flux:text>
        </div>

        <div class="shrink-0">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Save Rules') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </div>

    @if ($successMessage)
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ $successMessage }}
        </div>
    @endif

    <div class="space-y-4">
        @foreach ($groupedDefinitions as $group)
            <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md">{{ $group['label'] }}</flux:heading>
                </div>

                <div class="hidden grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,0.7fr))] gap-px bg-zinc-200 text-sm font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200 md:grid">
                    <div class="bg-zinc-50 px-4 py-3 dark:bg-zinc-900">{{ __('Notification') }}</div>
                    <div class="bg-zinc-50 px-4 py-3 text-center dark:bg-zinc-900">{{ __('In-app') }}</div>
                    <div class="bg-zinc-50 px-4 py-3 text-center dark:bg-zinc-900">{{ __('Email') }}</div>
                    <div class="bg-zinc-50 px-4 py-3 text-center dark:bg-zinc-900">{{ __('SMS') }}</div>
                    <div class="bg-zinc-50 px-4 py-3 text-center dark:bg-zinc-900">{{ __('Push') }}</div>
                </div>

                <div class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-950">
                    @foreach ($group['definitions'] as $definition)
                        <div wire:key="admin-notification-rule-{{ $definition['key'] }}" class="grid gap-4 px-4 py-4 md:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,0.7fr))] md:items-center">
                            <div>
                                <flux:heading size="sm">{{ $definition['label'] }}</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $definition['description'] }}</flux:text>
                                <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">{{ $definition['key'] }}</flux:text>
                            </div>

                            @foreach ($definition['channels'] as $channel)
                                <div class="flex items-center justify-between rounded-xl border border-zinc-200 px-3 py-2 md:justify-center md:border-0 md:px-0 md:py-0 dark:border-zinc-800">
                                    <div class="md:hidden">
                                        <flux:text class="font-medium">{{ $channel['label'] }}</flux:text>
                                    </div>

                                    <flux:switch
                                        wire:model.live="channels.{{ $definition['form_key'] }}.{{ $channel['key'] }}"
                                        :disabled="! $channel['supported']"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <x-action-message on="notification-channel-matrix-saved">
        {{ __('Saved.') }}
    </x-action-message>
</section>