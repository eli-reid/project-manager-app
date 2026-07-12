@props(['sections' => []])

<aside class="w-64 bg-white border-r min-h-screen p-4">
    <nav aria-label="Sidebar navigation" class="space-y-6">
        @foreach($sections as $section)
            <div class="section" data-key="{{ $section['key'] }}">
                <div class="section-label text-xs font-semibold text-gray-500 uppercase mb-2">{{ $section['label'] }}</div>

                @if(!empty($section['groups']))
                    <div class="space-y-3">
                        @foreach($section['groups'] as $group)
                            <div class="group">
                                <div class="group-heading flex items-center justify-between text-sm font-medium text-gray-700 px-2">
                                    <div class="flex items-center gap-2">
                                        @if(!empty($group['icon']))
                                            <span class="icon text-gray-400">{!! $group['icon'] !!}</span>
                                        @endif
                                        <span>{{ $group['label'] }}</span>
                                    </div>
                                </div>
                                <ul class="mt-1 space-y-1">
                                                    @foreach($group['items'] as $item)
                                                        <li>
                                                            @livewire(App\Core\Navigation\Livewire\NavItem::class, ['item' => $item])
                                                        </li>
                                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($section['items']))
                    <ul class="mt-2 space-y-1">
                        @foreach($section['items'] as $item)
                            <li>
                                @livewire(App\Core\Navigation\Livewire\NavItem::class, ['item' => $item])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
