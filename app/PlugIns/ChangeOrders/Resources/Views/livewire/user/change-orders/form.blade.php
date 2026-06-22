<div class="mx-auto max-w-2xl space-y-4">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $changeOrder?->exists ? 'Edit Change Order' : 'Create Change Order' }}</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">User-facing change order form is being upgraded to the new document workflow.</p>
    <a href="{{ route('change-orders.index') }}" class="inline-flex rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Back to change orders</a>
</div>
