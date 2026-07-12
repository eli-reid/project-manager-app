@props(['group'])

<flux:sidebar.group heading="{{ $group['label'] }}" :icon="'{{ $group['icon'] ?? '' }}'">
    <flux:menu>
        @foreach($group['items'] as $item)
            @livewire(App\Core\UI\Navigation\Livewire\NavItem::class, ['item' => $item])
        @endforeach
    </flux:menu>
</flux:sidebar.group>
