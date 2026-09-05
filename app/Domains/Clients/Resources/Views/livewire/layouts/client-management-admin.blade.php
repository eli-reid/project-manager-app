@php($navbarItems = \App\Domains\Clients\Livewire\Layouts\ClientManagementAdmin::navbarItems())

<x-layouts::domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</x-layouts::domain-layout>