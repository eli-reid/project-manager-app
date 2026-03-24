<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $invoice->vendor_name }}
                </h1>
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $invoice->status?->color() ?? '' }}">
                    {{ $invoice->status?->label() ?? 'Unknown' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $invoice->invoice_number ? 'Invoice #'.$invoice->invoice_number.' · ' : '' }}
                {{ $invoice->project?->name ?? 'No Project' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $invoice)
                <a href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
            @endcan
            <a href="{{ route('admin.invoices.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    {{-- Status Actions --}}
    @if ($invoice->isPending() || $invoice->isDraft() || $invoice->isVerified())
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Actions:</span>

            @can('verify', $invoice)
                <button wire:click="verify" wire:confirm="Mark this invoice as verified?" class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                    Verify
                </button>
            @endcan

            @can('markAsPaid', $invoice)
                <button wire:click="markAsPaid" wire:confirm="Mark this invoice as paid?" class="inline-flex items-center rounded-md border border-green-300 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50">
                    Mark as Paid
                </button>
            @endcan

            @can('reject', $invoice)
                <button wire:click="reject" wire:confirm="Reject this invoice?" class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                    Reject
                </button>
            @endcan
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        {{-- Invoice Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Project</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->project?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Vendor</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->vendor_name }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Invoice #</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->invoice_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Invoice Date</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->invoice_date->format('M j, Y') }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Due Date</dt>
                    <dd class="font-medium {{ $invoice->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                        {{ $invoice->due_date?->format('M j, Y') ?? '—' }}
                        @if ($invoice->isOverdue())
                            <span class="ml-1 text-xs font-normal">(overdue)</span>
                        @endif
                    </dd>
                </div>
                @if ($invoice->payment_date)
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500 dark:text-zinc-400">Payment Date</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->payment_date->format('M j, Y') }}</dd>
                    </div>
                @endif
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Created By</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->creator?->name ?? '—' }}</dd>
                </div>
                @if ($invoice->verifier)
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500 dark:text-zinc-400">Verified By</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->verifier->name }} on {{ $invoice->verified_at?->format('M j, Y') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Financial Summary --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Financial Summary</h2>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->subtotal, 2) }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->tax_amount, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-base dark:border-zinc-700">
                    <dt class="font-semibold text-zinc-900 dark:text-zinc-100">Total</dt>
                    <dd class="font-bold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->total_amount, 2) }}</dd>
                </div>
            </dl>

            @if ($invoice->notes)
                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</h3>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Line Items --}}
    @if ($invoice->lineItems->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Line Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</th>
                            <th class="w-24 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Qty</th>
                            <th class="w-32 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Unit Price</th>
                            <th class="w-32 pb-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->lineItems as $item)
                            <tr wire:key="item-{{ $item->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2.5 pr-4 text-sm text-zinc-900 dark:text-zinc-100">{{ $item->description }}</td>
                                <td class="py-2.5 pr-4 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $item->quantity, 2) }}</td>
                                <td class="py-2.5 pr-4 text-right text-sm text-zinc-700 dark:text-zinc-300">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="py-2.5 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">${{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="pt-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">Total</td>
                            <td class="pt-3 text-right text-sm font-bold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    {{-- Danger Zone --}}
    @can('delete', $invoice)
        <div class="rounded-xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-900 dark:bg-zinc-900">
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-red-500">Danger Zone</h2>
            <p class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">Permanently delete this invoice. This cannot be undone.</p>
            <button wire:click="delete" wire:confirm="Are you sure you want to delete this invoice? This cannot be undone." class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Delete Invoice
            </button>
        </div>
    @endcan
</div>
