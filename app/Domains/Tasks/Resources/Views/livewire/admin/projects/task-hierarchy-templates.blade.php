<div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Task Templates</h3>
        <a href="{{ $manageUrl }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Manage Templates</a>
    </div>

    <ul class="space-y-2">
        @forelse ($templates as $template)
            <li class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $template['name'] }}</span>
                <span class="text-zinc-500">({{ $template['priorityLabel'] }})</span>
            </li>
        @empty
            <li class="rounded-lg border border-zinc-200 bg-white px-3 py-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">No templates available.</li>
        @endforelse
    </ul>
</div>