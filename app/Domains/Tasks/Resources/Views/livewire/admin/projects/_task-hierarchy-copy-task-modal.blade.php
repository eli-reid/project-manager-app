<div x-show="showCopyTaskModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4" @click.self="showCopyTaskModal = false">
    <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Copy Task</h3>
            <button type="button" @click="showCopyTaskModal = false" class="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close">x</button>
        </div>

        <form wire:submit="copyTask" class="space-y-4">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" wire:model="copyIncludeSubtasksOnTask" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" />
                <span>Include subtasks</span>
            </label>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <button type="button" @click="showCopyTaskModal = false" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                <button type="submit" @click="showCopyTaskModal = false" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Copy Task</button>
            </div>
        </form>
    </div>
</div>
