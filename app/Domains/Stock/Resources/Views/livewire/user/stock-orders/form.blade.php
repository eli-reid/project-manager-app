<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit Stock Order' : 'New Stock Order' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $isEdit ? 'Update your order details and items.' : 'Submit a request for materials or supplies.' }}</p>
        </div>
        <a href="{{ route('stock-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Order Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Order Details</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project (optional)</label>
                    <select wire:model="project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">— No project —</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">
                                {{ $project->name }}{{ $project->project_number ? ' (#'.$project->project_number.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</label>
                    <select wire:model="urgency" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        @foreach ($urgencies as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('urgency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">PO Number (optional)</label>
                    <input type="text" wire:model="po_number" placeholder="e.g. PO-12345" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('po_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes (optional)</label>
                    <textarea wire:model="notes" rows="3" placeholder="Any additional notes or instructions..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</h2>
                <button type="button" wire:click="addItem" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    + Add Item
                </button>
            </div>

            @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3">
                @foreach ($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="flex items-start gap-3 rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="flex-1 grid gap-3 sm:grid-cols-5">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">Item Name</label>
                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.item_name"
                                    placeholder="e.g. 2x4 Lumber"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                                @error("items.{$index}.item_name") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">Qty</label>
                                <input
                                    type="number"
                                    wire:model="items.{{ $index }}.quantity"
                                    min="1"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                                @error("items.{$index}.quantity") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">Notes</label>
                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.notes"
                                    placeholder="Optional"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="removeItem({{ $index }})"
                            class="mt-6 shrink-0 rounded p-1 text-zinc-400 hover:bg-zinc-200 hover:text-red-600 dark:hover:bg-zinc-700 dark:hover:text-red-400"
                            @if (count($items) <= 1) disabled @endif
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('stock-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                <span wire:loading.remove>{{ $isEdit ? 'Update Order' : 'Submit Order' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
