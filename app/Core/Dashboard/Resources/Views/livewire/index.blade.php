<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    @forelse($sections as $sectionKey => $widgets)
        <div class="flex flex-col gap-3">
            @if ($showLabels)
                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ $sectionLabels[$sectionKey] ?? ucfirst($sectionKey) }}
                </h2>
            @endif
            <div class="grid gap-4 lg:grid-cols-6 items-start" style="grid-auto-rows: minmax(8rem, auto); grid-auto-flow: dense;">
                @php
                    $isSingleWidgetSection = count($widgets) === 1;
                @endphp
                @foreach($widgets as $widget)
                    @php
                        // Determine effective width/height (columns/rows). Fall back to
                        // legacy span mapping when width isn't provided.
                        $w = $widget['width'] ?? (
                            $widget['span'] === 'full' ? 6 : ($widget['span'] === 'half' ? 3 : 2)
                        );
                        $h = $widget['height'] ?? 1;

                        // If the section only contains a single widget always span full width.
                        if ($isSingleWidgetSection) {
                            $w = 6;
                        }
                    @endphp

                    <div class="min-h-0 h-full overflow-hidden" style="grid-column: span {{ $w }}; grid-row: span {{ $h }};">
                        <div class="h-full min-h-0 overflow-hidden">
                            @livewire($widget['component'], [], $widget['key'])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="flex items-center justify-center rounded-xl border border-zinc-200 p-12 dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Nothing to show here yet.</p>
        </div>
    @endforelse
</div>