@php($navbarItems = \App\Domains\Payroll\Livewire\Layouts\PayrollAdmin::navbarItems())

<x-layouts::domain-layout :title="$title ?? null" :navbar-items="$navbarItems">
    {{ $slot }}
</x-layouts::domain-layout>