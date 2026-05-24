@if ($items !== [])
    <div class="w-full">
        <flux:navbar class="flex flex-wrap items-center gap-2">
            @foreach ($items as $item)
                @if (($item['visible'] ?? true) !== false)
                    <flux:navbar.item :href="$item['href']" :current="$item['current']" wire:navigate>
                        {{ $item['label'] }}
                    </flux:navbar.item>
                @endif
            @endforeach
        </flux:navbar>
    </div>
@endif