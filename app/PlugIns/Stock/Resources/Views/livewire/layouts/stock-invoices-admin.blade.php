@php($navbarItems = \App\Domains\Stock\Livewire\Layouts\StockInvoicesAdmin::navbarItems())

<x-layouts::domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</x-layouts::domain-layout>