@component('core::layouts.settings-admin', ['title' => __('Settings Management')])
    <div class="flex w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Settings Management') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Manage application settings and configuration') }}</flux:text>
            </div>
        </div>

        <div class="grid gap-4">
            <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Edit Settings') }}</flux:heading>
                <div class="mt-3">
                    <livewire:core.settings.settings-editor />
                </div>
            </section>
        </div>
    </div>
@endcomponent
