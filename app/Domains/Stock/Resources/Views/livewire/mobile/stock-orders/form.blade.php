<x-slot:headerAction>
    <div class="flex items-center gap-2">
        <span
            wire:dirty
            wire:target="project_id,urgency,po_number,notes,items"
            class="inline-flex h-8 items-center rounded-full border border-amber-700/60 bg-amber-900/40 px-2.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-amber-200"
        >
            {{ __('Unsaved') }}
        </span>

        <button
            type="submit"
            form="mobile-stock-order-form"
            data-mobile-haptic
            class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-100 px-3 text-xs font-semibold text-zinc-900 active:bg-zinc-300"
        >
            <span wire:loading.remove wire:target="save">{{ $isEdit ? __('Save') : __('Create') }}</span>
            <span wire:loading wire:target="save">{{ $isEdit ? __('Saving…') : __('Creating…') }}</span>
        </button>
    </div>
</x-slot:headerAction>

<div class="flex flex-col gap-5 px-4 py-5 pb-24">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Stock Order') }}</p>
        <p class="mt-1 text-base font-semibold text-zinc-100">
            {{ $isEdit ? __('Update an existing request') : __('Create a new request') }}
        </p>
        <p class="mt-1 text-xs text-zinc-400">
            {{ __('Use the larger touch targets below to capture project details and requested items quickly.') }}
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form id="mobile-stock-order-form" wire:submit="save" class="flex flex-col gap-5">
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Order Details') }}</p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Project (optional)') }}</label>
                    <select
                        wire:model="project_id"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                    >
                        <option value="">{{ __('No project') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">
                                {{ $project->name }}{{ $project->project_number ? ' (#'.$project->project_number.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Urgency') }}</label>
                    <select
                        wire:model="urgency"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                    >
                        @foreach ($urgencies as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('urgency')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('PO Number (optional)') }}</label>
                    <input
                        type="text"
                        wire:model="po_number"
                        placeholder="{{ __('e.g. PO-12345') }}"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:border-zinc-500 focus:outline-none"
                    />
                    @error('po_number')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes (optional)') }}</label>
                    <textarea
                        wire:model="notes"
                        rows="3"
                        placeholder="{{ __('Add any special instructions...') }}"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:border-zinc-500 focus:outline-none"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Items') }}</p>

                <button
                    type="button"
                    wire:click="addItem"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 px-3 text-xs font-semibold text-zinc-200 active:bg-zinc-800"
                    data-mobile-haptic
                >
                    {{ __('Add item') }}
                </button>
            </div>

            @error('items')
                <p class="mt-3 text-xs text-red-400">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex flex-col gap-3">
                @foreach ($items as $index => $item)
                    <div wire:key="mobile-stock-order-item-form-{{ $index }}" class="rounded-2xl border border-zinc-800 bg-zinc-950/70 px-4 py-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Item :n', ['n' => $index + 1]) }}</p>

                            <button
                                type="button"
                                wire:click="removeItem({{ $index }})"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-800/60 bg-rose-900/30 text-rose-300 disabled:opacity-50"
                                @if (count($items) <= 1) disabled @endif
                                aria-label="{{ __('Remove item') }}"
                                data-mobile-haptic
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Item name') }}</label>
                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.item_name"
                                    placeholder="{{ __('e.g. 2x4 Lumber') }}"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:border-zinc-500 focus:outline-none"
                                />
                                @error("items.{$index}.item_name")
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Quantity') }}</label>
                                    <input
                                        type="number"
                                        min="1"
                                        wire:model="items.{{ $index }}.quantity"
                                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                    />
                                    @error("items.{$index}.quantity")
                                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes') }}</label>
                                    <input
                                        type="text"
                                        wire:model="items.{{ $index }}.notes"
                                        placeholder="{{ __('Optional') }}"
                                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:border-zinc-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </form>
</div>
