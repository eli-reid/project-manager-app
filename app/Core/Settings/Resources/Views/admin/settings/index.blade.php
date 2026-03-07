<x-layouts::app :title="__('Settings Management')">
    <div class="flex w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Settings Management') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Manage application settings and configuration') }}</flux:text>
            </div>

            <a href="{{ route('admin.settings.export') }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" data-test="admin-settings-export-link">
                {{ __('Export') }}
            </a>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-4 xl:col-span-3">
                <flux:heading size="lg">{{ __('Setting Groups') }}</flux:heading>
                <div class="mt-3">
                    <livewire:app.core.settings.livewire.settings-group-list />
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-8 xl:col-span-9">
                <flux:heading size="lg">{{ __('Edit Settings') }}</flux:heading>
                <div class="mt-3">
                    <livewire:app.core.settings.livewire.settings-editor />
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
