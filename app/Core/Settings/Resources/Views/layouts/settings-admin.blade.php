<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl space-y-2">
            @if (request()->routeIs('admin.settings.index'))
                <livewire:app.core.settings.livewire.settings-group-list :as-navbar="true" />
            @endif
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
