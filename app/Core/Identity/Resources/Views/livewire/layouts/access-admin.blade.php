@php($navbarItems = \App\Core\Identity\Livewire\Layouts\AccessAdmin::navbarItems())

<x-layouts::domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</x-layouts::domain-layout>