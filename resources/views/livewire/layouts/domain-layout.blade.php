<x-layouts::app :title="$title">
    <x-slot:domainNavbar>
        <livewire:layouts.domain-navbar :items="$navbarItems" />
    </x-slot:domainNavbar>

    {{ $slot }}
</x-layouts::app>