@php($navbarItems = \App\Livewire\Layouts\StockInvoicesAdmin::navbarItems())

<livewire:layouts.domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</livewire:layouts.domain-layout>