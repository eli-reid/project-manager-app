@php($navbarItems = \App\Core\Identity\Livewire\Layouts\AccessAdmin::navbarItems())

<livewire:layouts.domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</livewire:layouts.domain-layout>