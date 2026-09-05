<div x-show="showCopyCategoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4" @click.self="showCopyCategoryModal = false">
    <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Copy Category</h3>
            <button type="button" @click="showCopyCategoryModal = false" class="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close">x</button>
        </div>

        <form wire:submit="copyCategory" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Source Category</label>
                <select wire:model="copyCategorySourceId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select a source category</option>
                    @foreach ($copyCategoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('copyCategorySourceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Destination Parent</label>
                <select wire:model="copyCategoryDestinationParentId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Keep current parent</option>
                    <option value="__root__">Top level</option>
                    @foreach ($copyCategoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Choose the parent category the copied branch should be created under.</p>
                @error('copyCategoryDestinationParentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Options</label>
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="copyIncludeCategoryTasks" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" />
                    <span>Include tasks from this category</span>
                </label>
                <br />
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="copyIncludeChildCategories" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" />
                    <span>Include child categories (and their tasks)</span>
                </label>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">How Many Copies</label>
                    <input type="number" wire:model="copyCategoryQuantity" min="1" max="50" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('copyCategoryQuantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name Prefix (Optional)</label>
                    <input type="text" wire:model="copyCategoryNamePrefix" placeholder="Unit" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('copyCategoryNamePrefix') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Starting Number (Optional)</label>
                <input type="number" wire:model="copyCategoryStartNumber" min="1" max="9999" placeholder="201" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Example: prefix "Unit" + start 201 creates Unit 201, Unit 202, and so on.</p>
                @error('copyCategoryStartNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <button type="button" @click="showCopyCategoryModal = false" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Copy Category</button>
            </div>
        </form>
    </div>
</div>
