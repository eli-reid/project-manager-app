<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('viewAll', \App\Domains\Timecards\Models\Timecard::class)
                    <flux:navbar.item
                        :href="route('admin.timecards.index')"
                        :current="request()->routeIs('admin.timecards.*')"
                        wire:navigate
                    >
                        {{ __('Timecards') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAll', \App\Domains\Dailies\Models\DailyReport::class)
                    <flux:navbar.item
                        :href="route('admin.dailies.index')"
                        :current="request()->routeIs('admin.dailies.*')"
                        wire:navigate
                    >
                        {{ __('Dailies') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>