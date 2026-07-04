<x-layouts::app :title="$title ?? null">
    <x-slot:domainNavbar>
            @if (request()->routeIs('admin.settings.index'))
                <livewire:core.settings::settings-group-list :as-navbar="true" />
            @endif
    </x-slot:domainNavbar>
    {{ $slot }}
</x-layouts::app>
