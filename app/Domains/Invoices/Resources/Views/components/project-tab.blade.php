<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Project Invoices</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $invoiceCount }} {{ \Illuminate\Support\Str::plural('invoice', (int) $invoiceCount) }} linked to this project.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @can('create', \App\Domains\Invoices\Models\Invoice::class)
                <a href="{{ route('admin.invoices.create') }}" wire:navigate class="inline-flex items-center rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    New Invoice
                </a>
            @endcan

            <a href="{{ route('admin.invoices.index', ['project' => $project->id]) }}" wire:navigate class="inline-flex items-center rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                View All Invoices
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($invoices as $invoice)
                        <tr wire:key="project-invoice-{{ $invoice->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->vendor_name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $invoice->invoice_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $invoice->invoice_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold {{ $invoice->status?->color() ?? '' }}">
                                    {{ $invoice->status?->label() ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" wire:navigate class="inline-flex items-center rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No invoices found for this project.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
