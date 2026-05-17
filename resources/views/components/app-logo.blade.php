@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="/apple-touch-icon.png" alt="{{ config('app.name') }}" class="size-8 object-cover" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="/apple-touch-icon.png" alt="{{ config('app.name') }}" class="size-8 object-cover" />
        </x-slot>
    </flux:brand>
@endif
