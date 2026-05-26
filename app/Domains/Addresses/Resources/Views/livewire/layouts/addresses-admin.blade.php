@php($navbarItems = \App\Domains\Addresses\Livewire\Layouts\AddressesAdmin::navbarItems())

<livewire:layouts.domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</livewire:layouts.domain-layout>