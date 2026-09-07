<div
    x-data="{
        zoom: @js($zoom),
        x: @js($centerX),
        y: @js($centerY),
        markup: true,
        palette: false,
        fullScreen: false,
        persist() { $wire.persistViewState(this.zoom, this.x, this.y) },
        fit() { this.zoom = 1; this.x = .5; this.y = .5; this.persist() },
        zoomBy(amount) { this.zoom = Math.max(.25, Math.min(4, this.zoom + amount)); this.persist() },
        keydown(event) {
            if (event.key === 'Escape') { this.palette = false; this.fullScreen = false }
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); this.palette = true }
            if (event.key === '+') this.zoomBy(.25)
            if (event.key === '-') this.zoomBy(-.25)
            if (event.key === '0') this.fit()
            if (event.key.toLowerCase() === 'm') this.markup = !this.markup
            if (event.key.toLowerCase() === 'f') this.fullScreen = !this.fullScreen
        }
    }"
    x-on:keydown.window="keydown($event)"
    class="min-h-[calc(100vh-8rem)] bg-zinc-950 text-white"
    :class="{ 'fixed inset-0 z-50 min-h-screen': fullScreen }"
>
    <div class="flex items-center justify-between gap-3 border-b border-zinc-800 bg-zinc-900 px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <flux:button href="{{ route('plans.sheets.index', $project) }}" wire:navigate variant="ghost" icon="arrow-left">Sheets</flux:button>
            <div class="min-w-0">
                <flux:text class="font-semibold text-white">{{ $sheet->sheet_number ?: 'Unnumbered' }}</flux:text>
                <flux:text class="block truncate text-xs text-zinc-400">{{ $sheet->title ?: 'Untitled sheet' }}</flux:text>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <flux:button wire:click="selectRevision('{{ $revision->id }}')" variant="ghost">Revision {{ $revision->revision_label ?: 'Current' }}</flux:button>
            <flux:button x-on:click="zoomBy(-.25)" variant="ghost">−</flux:button>
            <flux:button x-on:click="fit()" variant="ghost">Fit</flux:button>
            <flux:button x-on:click="zoomBy(.25)" variant="ghost">+</flux:button>
            <flux:button x-on:click="markup = !markup" variant="ghost">Markup</flux:button>
            <flux:button x-on:click="fullScreen = !fullScreen" variant="ghost">Fullscreen</flux:button>
        </div>
    </div>

    <div class="grid min-h-[calc(100vh-12rem)] grid-cols-[4.5rem_minmax(0,1fr)_18rem]">
        @island(name: 'thumbnail-rail')
            <aside class="overflow-y-auto border-r border-zinc-800 p-2">
                <div class="space-y-2">
                    @foreach ($siblings as $sibling)
                        <a wire:key="viewer-sibling-{{ $sibling->id }}" href="{{ route('plans.sheets.show', [$project, $sibling]) }}" wire:navigate class="block rounded-lg p-1 {{ $sibling->is($sheet) ? 'bg-sky-600' : 'bg-zinc-800 hover:bg-zinc-700' }}">
                            <div class="flex aspect-[4/3] items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold">{{ $sibling->sheet_number ?: '?' }}</div>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endisland

        <main class="relative overflow-hidden bg-zinc-900" x-on:wheel.prevent="zoomBy($event.deltaY > 0 ? -.1 : .1)">
            <div class="absolute inset-0 flex items-center justify-center p-8">
                <div class="relative origin-center transition-transform duration-75" :style="`transform: translate(${(x - .5) * 100}%, ${(y - .5) * 100}%) scale(${zoom})`">
                    @if ($revision->preview_path)
                        <img src="{{ route('plans.images', [$revision, 'preview']) }}" alt="{{ $sheet->sheet_number }} {{ $sheet->title }}" class="max-h-[calc(100vh-14rem)] max-w-full select-none object-contain" draggable="false">
                    @elseif ($revision->thumbnail_path)
                        <img src="{{ route('plans.images', [$revision, 'thumb']) }}" alt="{{ $sheet->sheet_number }} {{ $sheet->title }}" class="max-h-[calc(100vh-14rem)] max-w-full select-none object-contain" draggable="false">
                    @else
                        <div class="flex h-96 w-[32rem] items-center justify-center rounded bg-zinc-800 text-zinc-400">Preview unavailable</div>
                    @endif
                    <canvas x-show="markup" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                </div>
            </div>
        </main>

        <aside class="overflow-y-auto border-l border-zinc-800 bg-zinc-900 p-4">
            <flux:heading size="sm" class="text-white">Details</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-zinc-500">Sheet</dt><dd>{{ $sheet->sheet_number ?: 'Unnumbered' }}</dd></div>
                <div><dt class="text-zinc-500">Title</dt><dd>{{ $sheet->title ?: 'Untitled sheet' }}</dd></div>
                <div><dt class="text-zinc-500">Discipline</dt><dd>{{ $sheet->discipline ?: 'Unassigned' }}</dd></div>
                <div><dt class="text-zinc-500">Revision</dt><dd>{{ $revision->revision_label ?: 'Current' }}</dd></div>
            </dl>
            <flux:heading size="sm" class="mt-8 text-white">Revisions</flux:heading>
            <div class="mt-3 space-y-2">
                @foreach ($sheet->revisions->sortByDesc('created_at') as $sheetRevision)
                    <button wire:key="viewer-revision-{{ $sheetRevision->id }}" wire:click="selectRevision('{{ $sheetRevision->id }}')" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm {{ $sheetRevision->id === $revision->id ? 'bg-sky-600' : 'bg-zinc-800 hover:bg-zinc-700' }}">
                        <span>{{ $sheetRevision->revision_label ?: 'Revision' }}</span>
                        @if ($sheetRevision->is_current)<span class="text-xs text-sky-200">Current</span>@endif
                    </button>
                @endforeach
            </div>
        </aside>
    </div>

    <div x-show="palette" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-8" x-on:click.self="palette = false">
        <div class="w-full max-w-xl rounded-xl border border-zinc-700 bg-zinc-900 p-4 shadow-2xl">
            <flux:input x-ref="jumpSearch" x-init="$watch('palette', value => value && $nextTick(() => $refs.jumpSearch.focus()))" placeholder="Jump to sheet number or title..." />
            <div class="mt-3 max-h-80 space-y-1 overflow-y-auto">
                @foreach ($siblings as $sibling)
                    <a wire:key="palette-sheet-{{ $sibling->id }}" href="{{ route('plans.sheets.show', [$project, $sibling]) }}" wire:navigate x-on:click="palette = false" class="block rounded-lg px-3 py-2 hover:bg-zinc-800">{{ $sibling->sheet_number }} — {{ $sibling->title }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
