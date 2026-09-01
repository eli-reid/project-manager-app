<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit Invoice' : 'Create Invoice' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $isEdit ? 'Update invoice details, totals, and optional line items.' : 'Enter invoice totals first, then add line items only if needed.' }}</p>
        </div>
        <a href="{{ $isEdit ? route('admin.invoices.show', $invoice) : route('admin.invoices.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Invoice Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice Details</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project <span class="text-red-500">*</span></label>
                    <select wire:model="project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Select a project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Accounting Code</label>
                    <select wire:model="accounting_code_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">No accounting code</option>
                        @foreach ($accountingCodes as $accountingCode)
                            <option value="{{ $accountingCode->id }}">{{ $accountingCode->code }} - {{ $accountingCode->name }}</option>
                        @endforeach
                    </select>
                    @error('accounting_code_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vendor Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="vendor_name" placeholder="e.g. ABC Supply Co." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('vendor_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice Number</label>
                    <input type="text" wire:model="invoice_number" placeholder="e.g. INV-1001" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('invoice_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="invoice_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('invoice_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Due Date</label>
                    <input type="date" wire:model="due_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status <span class="text-red-500">*</span></label>
                    <select wire:model="status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</label>
                    <textarea wire:model="notes" rows="3" placeholder="Any additional notes about this invoice..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Invoice Totals --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice Totals</h2>
            <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                Enter totals directly for fast entry. Add line items only when you need the detailed breakdown.
            </p>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Subtotal <span class="text-red-500">*</span></label>
                    <div class="flex h-10 items-center rounded-lg border border-zinc-300 bg-white focus-within:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                        <span class="pl-3 pr-2 text-sm text-zinc-400">$ </span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="subtotal"
                            @disabled(count($lineItems) > 0)
                            class="h-full w-full border-0 bg-transparent pr-3 text-sm text-zinc-900 [appearance:textfield] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:bg-zinc-100 dark:text-zinc-100 dark:disabled:bg-zinc-900"
                        />
                    </div>
                    @error('subtotal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if (count($lineItems) > 0)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Subtotal is being calculated from line items.</p>
                    @endif
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Tax Amount <span class="text-red-500">*</span></label>
                    <div class="flex h-10 items-center rounded-lg border border-zinc-300 bg-white focus-within:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                        <span class="pl-3 pr-2 text-sm text-zinc-400">$</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="tax_amount"
                            class="h-full w-full border-0 bg-transparent pr-3 text-sm text-zinc-900 [appearance:textfield] focus:outline-none focus:ring-0 dark:text-zinc-100"
                        />
                    </div>
                    @error('tax_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Amount</label>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        ${{ number_format((float) $total_amount, 2) }}
                    </div>
                    <input type="hidden" wire:model="total_amount" />
                    @error('total_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items (Optional) --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Line Items (Optional)</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Use this only when you want item-level breakdown for reporting.</p>
                </div>
                <button type="button" wire:click="addLineItem" class="rounded-md bg-zinc-100 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    + Add Item
                </button>
            </div>

            @error('lineItems') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            @if (count($lineItems) === 0)
                <div class="rounded-lg border border-dashed border-zinc-300 px-4 py-5 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    No line items added. Totals-only entry is enabled.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</th>
                                <th class="w-24 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Qty</th>
                                <th class="w-32 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Unit Price</th>
                                <th class="w-32 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                                <th class="w-10 pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lineItems as $i => $item)
                                <tr wire:key="line-item-{{ $i }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="py-2 pr-3">
                                        <input
                                            type="text"
                                            wire:model.live="lineItems.{{ $i }}.description"
                                            placeholder="Item description"
                                            class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                        />
                                        @error("lineItems.$i.description") <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-2 pr-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            wire:model.live="lineItems.{{ $i }}.quantity"
                                            class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-right text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                        />
                                        @error("lineItems.$i.quantity") <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-2 pr-3">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-2 flex items-center text-sm text-zinc-400">$</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                wire:model.live="lineItems.{{ $i }}.unit_price"
                                                class="w-full rounded border border-zinc-300 bg-white py-1.5 pl-5 pr-2 text-right text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                        </div>
                                        @error("lineItems.$i.unit_price") <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-2 pr-3 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        ${{ number_format((float) ($item['total'] ?? 0), 2) }}
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" wire:click="removeLineItem({{ $i }})" class="rounded p-1 text-zinc-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ $isEdit ? route('admin.invoices.show', $invoice) : route('admin.invoices.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                <span wire:loading.remove>{{ $isEdit ? 'Update Invoice' : 'Create Invoice' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
