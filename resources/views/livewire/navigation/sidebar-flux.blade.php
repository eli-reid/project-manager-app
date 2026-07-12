@props(['sections' => []])

<flux:sidebar class="h-full">
    @foreach($sections as $section)
        @if(!empty($section['groups']))
            @foreach($section['groups'] as $group)
                <flux:sidebar.group heading="{{ $group['label'] }}" :icon="'{{ $group['icon'] ?? '' }}'">
                    <flux:menu>
                        @foreach($group['items'] as $item)
                            <flux:menu.item :href="'{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}'">
                                {{ $item['label'] }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:sidebar.group>
            @endforeach
        @endif

        @if(!empty($section['items']))
            <flux:sidebar.group>
                <flux:menu>
                    @foreach($section['items'] as $item)
                        <flux:menu.item :href="'{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}'">
                            {{ $item['label'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:sidebar.group>
        @endif
    @endforeach
</flux:sidebar>
