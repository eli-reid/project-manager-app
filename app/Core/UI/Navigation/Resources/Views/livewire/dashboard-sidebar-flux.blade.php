@props(['sections' => []])

<div class="mt-6 flex-1 space-y-5">
    @foreach($sections as $section)
        <section class="space-y-2">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-zinc-500">{{ $section['label'] }}</p>

            @if(! empty($section['items']))
                <div class="space-y-2">
                    @foreach($section['items'] as $item)
                        <a
                            href="{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}"
                            wire:navigate
                            data-test="{{ $item['id'] }}-link"
                            class="group flex items-start gap-3 rounded-2xl px-3 py-3 transition {{ $item['active']
                                ? 'bg-amber-500/15 text-white ring-1 ring-amber-400/30'
                                : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-100' }}"
                        >
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl {{ $item['active'] ? 'bg-amber-400/20 text-amber-300' : 'bg-white/5 text-zinc-500 group-hover:text-zinc-300' }}">
                                @if(! empty($item['icon_html']))
                                    {!! $item['icon_html'] !!}
                                @elseif(! empty($item['icon']) && view()->exists("flux.icon.{$item['icon']}"))
                                    {!! view("flux.icon.{$item['icon']}", ['variant' => 'micro'])->render() !!}
                                @else
                                    {{ strtoupper(substr($item['label'], 0, 1)) }}
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $item['label'] }}</span>
                                @if (($item['meta']['description'] ?? '') !== '')
                                    <span class="mt-1 block text-xs leading-5 text-zinc-500 group-hover:text-zinc-400">{{ $item['meta']['description'] }}</span>
                                @endif
                            </span>
                            @if (($item['meta']['badge'] ?? '') !== '')
                                <span class="rounded-full border border-white/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-300">{{ $item['meta']['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif

            @if(! empty($section['groups']))
                <div class="space-y-4">
                    @foreach($section['groups'] as $group)
                        <div class="space-y-2">
                            <p class="px-3 text-xs font-semibold text-zinc-500">{{ $group['label'] }}</p>
                            @foreach($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}"
                                    wire:navigate
                                    data-test="{{ $item['id'] }}-link"
                                    class="group flex items-start gap-3 rounded-2xl px-3 py-3 transition {{ $item['active']
                                        ? 'bg-amber-500/15 text-white ring-1 ring-amber-400/30'
                                        : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-100' }}"
                                >
                                    <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl {{ $item['active'] ? 'bg-amber-400/20 text-amber-300' : 'bg-white/5 text-zinc-500 group-hover:text-zinc-300' }}">
                                        @if(! empty($item['icon_html']))
                                            {!! $item['icon_html'] !!}
                                        @elseif(! empty($item['icon']) && view()->exists("flux.icon.{$item['icon']}"))
                                            {!! view("flux.icon.{$item['icon']}", ['variant' => 'micro'])->render() !!}
                                        @else
                                            {{ strtoupper(substr($item['label'], 0, 1)) }}
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $item['label'] }}</span>
                                        @if (($item['meta']['description'] ?? '') !== '')
                                            <span class="mt-1 block text-xs leading-5 text-zinc-500 group-hover:text-zinc-400">{{ $item['meta']['description'] }}</span>
                                        @endif
                                    </span>
                                    @if (($item['meta']['badge'] ?? '') !== '')
                                        <span class="rounded-full border border-white/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-300">{{ $item['meta']['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</div>