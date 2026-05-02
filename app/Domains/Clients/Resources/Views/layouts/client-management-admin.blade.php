<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('viewAny', \App\Domains\Clients\Models\Client::class)
                    <flux:navbar.item
                        :href="route('admin.clients.index')"
                        :current="request()->routeIs('admin.clients.*')"
                        wire:navigate
                    >
                        {{ __('Clients') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Domains\Addresses\Models\Address::class)
                    <flux:navbar.item
                        :href="route('admin.addresses.index')"
                        :current="request()->routeIs('admin.addresses.*')"
                        wire:navigate
                    >
                        {{ __('Addresses') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>