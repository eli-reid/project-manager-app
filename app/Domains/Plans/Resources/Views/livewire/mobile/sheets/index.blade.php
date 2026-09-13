<div class="space-y-4 p-4">
    <flux:heading size="lg">Plans</flux:heading>
    <div class="grid grid-cols-2 gap-3">
        @foreach ($sheets as $sheet)
            <a wire:key="mobile-plan-sheet-{{ $sheet->id }}" href="{{ route('plans.mobile.sheets.show', [$project, $sheet]) }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex aspect-[4/3] items-center justify-center rounded-lg bg-zinc-100 text-xs font-semibold dark:bg-zinc-800">{{ $sheet->sheet_number ?: '?' }}</div>
                <flux:text class="mt-2 truncate text-sm font-semibold">{{ $sheet->title ?: 'Untitled sheet' }}</flux:text>
            </a>
        @endforeach
    </div>
</div>
