@props(['item'])

<flux:menu.item :href="'{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}'" :class="' ' . ($item['active'] ? 'bg-gray-100 dark:bg-zinc-800' : '')">
    @if(! empty($item['icon_html']))
        {!! $item['icon_html'] !!}
    @elseif(! empty($item['icon']) && view()->exists("flux.icon.{$item['icon']}"))
        {!! view("flux.icon.{$item['icon']}", ['variant' => 'micro'])->render() !!}
    @endif
    {{ $item['label'] }}
</flux:menu.item>
