<div class="min-h-screen bg-zinc-950 text-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-800 bg-zinc-900 px-4 py-3">
        <div>
            <flux:button href="{{ route('plans.sheets.show', [$sheet->project_id, $sheet]) }}" wire:navigate variant="ghost">Back to viewer</flux:button>
            <flux:text class="ml-3 font-semibold text-white">{{ $sheet->sheet_number ?: 'Unnumbered' }} comparison</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['side-by-side' => 'Side by side', 'overlay' => 'Overlay', 'difference' => 'Difference'] as $value => $label)
                <flux:button wire:key="compare-mode-{{ $value }}" wire:click="selectMode('{{ $value }}')" variant="{{ $mode === $value ? 'primary' : 'ghost' }}">{{ $label }}</flux:button>
            @endforeach
            <flux:checkbox wire:model.live="locked" label="Lock pan and zoom" />
        </div>
    </div>

    <div class="grid gap-4 p-4 {{ $mode === 'side-by-side' ? 'lg:grid-cols-2' : 'grid-cols-1' }}">
        @foreach (['left' => $left, 'right' => $right] as $side => $revision)
            <section wire:key="compare-pane-{{ $side }}" class="relative min-h-[70vh] overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <flux:text class="font-semibold text-white">{{ ucfirst($side) }} revision</flux:text>
                    <flux:select wire:model.live="{{ $side }}RevisionId">
                        @foreach ($revisions as $option)
                            <option wire:key="compare-{{ $side }}-revision-{{ $option->id }}" value="{{ $option->id }}">{{ $option->revision_label ?: $option->created_at?->format('M j, Y') }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex h-[calc(70vh-4rem)] items-center justify-center bg-zinc-950">
                    @if ($revision->preview_path)
                        <img src="{{ route('plans.images', [$revision, 'preview']) }}" alt="" class="max-h-full max-w-full object-contain {{ $mode === 'difference' ? 'mix-blend-difference' : '' }}">
                    @else
                        <flux:text class="text-zinc-500">Preview unavailable</flux:text>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</div>
