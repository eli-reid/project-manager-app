<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $changeOrder->title }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Status: {{ ucfirst($changeOrder->status) }}</p>
        </div>
        <a href="{{ route('change-orders.index') }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Back</a>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $changeOrder->description ?: 'No description provided.' }}</p>
    </div>
</div>
