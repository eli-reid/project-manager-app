<div class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $isEdit ? 'Edit Template' : 'New Template' }}
        </h1>
        <a href="{{ route('admin.stock-order-templates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Details --}}
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Details</h2>

            <div>
                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name <span class="text-red-500">*</span></label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    placeholder="Template name"
                />
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="3"
                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    placeholder="Optional description"
                ></textarea>
                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="urgency" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Default Urgency <span class="text-red-500">*</span></label>
                    <select
                        id="urgency"
                        wire:model="urgency"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        @foreach ($urgencies as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('urgency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Notes</label>
                    <input
                        id="notes"
                        type="text"
                        wire:model="notes"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="Optional notes"
                    />
                    @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="is_global" class="rounded border-zinc-300 text-zinc-800 focus:ring-zinc-500" />
                    Global (visible to all users)
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-zinc-800 focus:ring-zinc-500" />
                    Active
                </label>
            </div>
        </div>

        {{-- Template Items --}}
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</h2>
                <button type="button" wire:click="addItem" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">+ Add Item</button>
            </div>

            @if (count($templateItems) === 0)
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No items yet. Click "Add Item" to start.</p>
            @endif

            <div class="space-y-3">
                @foreach ($templateItems as $i => $item)
                    <div wire:key="item-{{ $i }}" class="flex gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="min-w-0 flex-1">
                            <input
                                type="text"
                                wire:model="templateItems.{{ $i }}.item_name"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                placeholder="Item name"
                            />
                            @error("templateItems.{$i}.item_name") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="w-24">
                            <input
                                type="number"
                                wire:model="templateItems.{{ $i }}.quantity"
                                min="1"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                placeholder="Qty"
                            />
                            @error("templateItems.{$i}.quantity") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="min-w-0 flex-1">
                            <input
                                type="text"
                                wire:model="templateItems.{{ $i }}.notes"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                placeholder="Notes (optional)"
                            />
                        </div>
                        <button type="button" wire:click="removeItem({{ $i }})" class="self-start rounded p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.stock-order-templates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <button type="submit" class="rounded-md bg-zinc-800 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $isEdit ? 'Update Template' : 'Create Template' }}
            </button>
        </div>
    </form>
</div>
