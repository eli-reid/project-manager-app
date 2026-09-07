<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">Sheet index</flux:heading>
        <div class="flex flex-wrap gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search sheets..." />
            <flux:select wire:model.live="discipline">
                <option value="">All disciplines</option>
                @foreach ($disciplines as $option)
                    <option wire:key="discipline-{{ $option }}" value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="setId">
                <option value="">All sets</option>
                @foreach ($sets as $set)
                    <option wire:key="set-filter-{{ $set->id }}" value="{{ $set->id }}">{{ $set->name }}</option>
                @endforeach
            </flux:select>
            <flux:button wire:click="$set('mode', '{{ $mode === 'grid' ? 'list' : 'grid' }}')" variant="ghost">{{ $mode === 'grid' ? 'List' : 'Grid' }}</flux:button>
        </div>
    </div>

    @if ($sheets->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No sheets match the current filters.</flux:text>
            <flux:button wire:click="clearFilters" variant="ghost" class="mt-3">Clear filters</flux:button>
        </div>
    @else
        <div class="{{ $mode === 'grid' ? 'grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6' : 'space-y-2' }}">
            @foreach ($sheets as $sheet)
                <a wire:key="plan-sheet-{{ $sheet->id }}" href="{{ route('plans.sheets.index', $project) }}" class="group rounded-xl border border-zinc-200 bg-white p-2 shadow-sm transition hover:border-sky-400 dark:border-zinc-700 dark:bg-zinc-900">
                    @if ($mode === 'grid')
                        <div class="aspect-[4/3] overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @if ($sheet->currentRevision?->thumbnail_path)
                                <img src="{{ route('plans.images', [$sheet->currentRevision, 'thumb']) }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                            @endif
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-2 p-2">
                        <div class="min-w-0">
                            <flux:text class="truncate font-semibold">{{ $sheet->sheet_number ?: 'Unnumbered' }}</flux:text>
                            <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $sheet->title ?: 'Untitled sheet' }}</flux:text>
                        </div>
                        @if ($sheet->annotations_count > 0)
                            <flux:badge size="sm">{{ $sheet->annotations_count }}</flux:badge>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        @if ($hasMore)
            <div wire:intersect="loadMore" class="py-4 text-center">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Loading more sheets…</flux:text>
            </div>
        @endif
    @endif
</section>
