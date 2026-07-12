@props(['sections' => []])

<flux:sidebar class="h-full">
    @foreach($sections as $section)
        @if(! empty($section['groups']))
            @foreach($section['groups'] as $group)
                @livewire(App\Core\UI\Navigation\Livewire\NavGroup::class, ['group' => $group])
            @endforeach
        @endif

        @if(! empty($section['items']))
            <flux:sidebar.group>
                <flux:menu>
                    @foreach($section['items'] as $item)
                        @livewire(App\Core\UI\Navigation\Livewire\NavItem::class, ['item' => $item])
                    @endforeach
                </flux:menu>
            </flux:sidebar.group>
        @endif
    @endforeach
</flux:sidebar>
