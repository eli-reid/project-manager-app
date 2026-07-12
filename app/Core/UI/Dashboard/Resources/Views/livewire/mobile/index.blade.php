<div class="flex min-w-0 flex-col gap-5 overflow-x-hidden px-4 py-5">
    @forelse($sections as $sectionKey => $widgets)
        <section class="flex min-w-0 flex-col gap-3">
            @if ($showLabels)
                <h2 class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">
                    {{ $sectionLabels[$sectionKey] ?? ucfirst($sectionKey) }}
                </h2>
            @endif

            <div class="grid gap-4">
                @foreach($widgets as $widget)
                    <div class="min-w-0 max-w-full overflow-x-auto">
                        @livewire($widget['component'], [], $widget['key'].'-mobile')
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-3xl border border-zinc-800 bg-zinc-900 p-6 text-sm text-zinc-400">
            {{ __('Nothing to show here yet.') }}
        </div>
    @endforelse
</div>