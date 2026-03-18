<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
            @if (request()->routeIs('admin.settings.index'))
                <livewire:app.core.settings.livewire.settings-group-list :as-navbar="true" />
            @endif
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
