<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-[2rem]">
    @forelse($sections as $sectionKey => $widgets)
        <section class="flex flex-col gap-3">
            @if ($showLabels)
                <h2 class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                    {{ $sectionLabels[$sectionKey] ?? ucfirst($sectionKey) }}
                </h2>
            @endif

            <div class="grid gap-4 lg:grid-cols-6">
                @php
                    $isSingleWidgetSection = count($widgets) === 1;
                @endphp

                @foreach($widgets as $widget)
                    <div class="{{ $isSingleWidgetSection
                        ? 'lg:col-span-6'
                        : match($widget['span']) {
                            'full' => 'lg:col-span-6',
                            'half' => 'lg:col-span-3',
                            default => 'lg:col-span-2',
                        } }}">
                        @livewire($widget['component'], [], $widget['key'])
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="flex items-center justify-center rounded-[1.75rem] border border-zinc-200/80 bg-white/80 p-12 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/70">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Nothing to show here yet.</p>
        </div>
    @endforelse
</div>