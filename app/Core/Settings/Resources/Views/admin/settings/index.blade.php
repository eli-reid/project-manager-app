<x-layouts::admin :title="__('Settings Management')">
    <div class="flex w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Settings Management') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Manage application settings and configuration') }}</flux:text>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-3 xl:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg">{{ __('Setting Groups') }}</flux:heading>
                    <flux:text class="text-sm">{{ __('Pick one') }}</flux:text>
                </div>

                <div class="mt-3">
                    <livewire:app.core.settings.livewire.settings-group-list />
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-9 xl:col-span-10">
                <flux:heading size="lg">{{ __('Edit Settings') }}</flux:heading>
                <div class="mt-3">
                    <livewire:app.core.settings.livewire.settings-editor />
                </div>
            </section>
        </div>
    </div>
</x-layouts::admin>
