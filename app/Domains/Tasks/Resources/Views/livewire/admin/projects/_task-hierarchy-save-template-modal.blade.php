<div x-show="showSaveTemplateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4" @click.self="showSaveTemplateModal = false">
    <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Save Category as Template</h3>
            <button type="button" @click="showSaveTemplateModal = false" class="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close">x</button>
        </div>

        <form wire:submit="saveCategoryAsTemplate" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Template Name</label>
                <input type="text" wire:model="saveTemplateName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('saveTemplateName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                <textarea rows="2" wire:model="saveTemplateDescription" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                @error('saveTemplateDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @error('saveTemplateSourceCategoryId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <button type="button" @click="showSaveTemplateModal = false" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Save Template</button>
            </div>
        </form>
    </div>
</div>
