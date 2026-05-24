@php($navbarItems = \App\Livewire\Layouts\AccessAdmin::navbarItems())

<livewire:layouts.domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</livewire:layouts.domain-layout>