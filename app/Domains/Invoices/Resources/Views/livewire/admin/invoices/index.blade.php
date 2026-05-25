<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Invoices</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Track vendor invoices and costs across projects.</p>
        </div>
        @can('create', \App\Domains\Invoices\Models\Invoice::class)
            <a href="{{ route('admin.invoices.create') }}" wire:navigate class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                Create Invoice
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search vendor or invoice #..."
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
            />
        </div>
        <div>
            <select wire:model.live="projectFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All Projects</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All Statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($invoices as $invoice)
                        <tr
                            wire:key="invoice-{{ $invoice->id }}"
                            @click="if (! $event.target.closest('[data-prevent-row-nav]')) { window.Livewire?.navigate('{{ route('admin.invoices.show', $invoice) }}') ?? window.location.assign('{{ route('admin.invoices.show', $invoice) }}'); }"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                        >

                            <td class="px-4 py-3 align-top text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->vendor_name }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-500 dark:text-zinc-400">{{ $invoice->invoice_number ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $invoice->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $invoice->invoice_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 align-top text-sm {{ $invoice->isOverdue() ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $invoice->due_date?->format('M j, Y') ?? '—' }}
                                @if ($invoice->isOverdue())
                                    <span class="ml-1 text-xs">(overdue)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float)$invoice->total_amount, 2) }}</td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold {{ $invoice->status?->color() ?? '' }}">
                                    {{ $invoice->status?->label() ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top" data-prevent-row-nav x-on:click.stop="">
                                <livewire:ui.row-actions-dropdown label="Invoice actions" width="w-36" :menu-height="160">
                                    @can('view', $invoice)
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">View</a>
                                    @endcan
                                    @can('update', $invoice)
                                        <a href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                    @endcan
                                </livewire:ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
