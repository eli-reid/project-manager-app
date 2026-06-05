<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($cards as $card)
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</p>
            <p class="mt-1 text-lg font-semibold {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>
