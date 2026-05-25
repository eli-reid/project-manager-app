@php($navbarItems = \App\Domains\Clients\Livewire\Layouts\ClientManagementAdmin::navbarItems())

<livewire:layouts.domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</livewire:layouts.domain-layout>