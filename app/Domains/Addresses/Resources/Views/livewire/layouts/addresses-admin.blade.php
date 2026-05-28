@php($navbarItems = \App\Domains\Addresses\Livewire\Layouts\AddressesAdmin::navbarItems())

<x-layouts::domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</x-layouts::domain-layout>