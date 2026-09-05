@if ($showInlineCategoryForm)
    <form wire:submit="createInlineCategory" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-700 dark:bg-zinc-800/40">
        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Quick Add Category</p>
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</label>
                <input type="text" wire:model="inlineCategoryName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('inlineCategoryName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Existing numbers are incremented automatically. If the name has no number, the starting number is appended.
                </p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Parent</label>
                <select wire:model="inlineCategoryParentId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Top level</option>
                    @foreach ($copyCategoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('inlineCategoryParentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Category Count</label>
                <input type="number" min="1" max="100" wire:model="inlineCategoryBatchCount" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('inlineCategoryBatchCount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Starting Number</label>
                <input type="number" min="0" max="999999" wire:model="inlineCategoryBatchStartNumber" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('inlineCategoryBatchStartNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                <textarea wire:model="inlineCategoryDescription" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                @error('inlineCategoryDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex items-center justify-end gap-2">
            <button type="button" wire:click="cancelInlineCategoryForm" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Category</button>
        </div>
    </form>
@endif
