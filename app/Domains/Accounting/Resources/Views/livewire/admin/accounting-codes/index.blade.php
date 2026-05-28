<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Accounting Codes</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Group projects, invoices, and stock orders under shared accounting buckets.</p>
        </div>

        @can('create', \App\Domains\Accounting\Models\AccountingCode::class)
            <a href="{{ route('admin.accounting-codes.create') }}" wire:navigate class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Accounting Code</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search code or name..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div>
            <select wire:model.live="activeFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Projects</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoices</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Invoiced $</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Stock Orders</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($accountingCodes as $accountingCode)
                        <tr wire:key="accounting-code-{{ $accountingCode->id }}">
                            <td class="px-4 py-3 align-top text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $accountingCode->code }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                <div>{{ $accountingCode->name }}</div>
                                @if ($accountingCode->description)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $accountingCode->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                <span class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ str($accountingCode->account_type)->headline() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $accountingCode->projects_count }}</td>
                            <td class="px-4 py-3 align-top text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $accountingCode->invoices_count }}</td>
                            <td class="px-4 py-3 align-top text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) ($accountingCode->invoices_sum_total_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 align-top text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $accountingCode->stock_orders_count }}</td>
                            <td class="px-4 py-3 align-top text-sm">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $accountingCode->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    {{ $accountingCode->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                @can('update', $accountingCode)
                                    <a href="{{ route('admin.accounting-codes.edit', $accountingCode) }}" wire:navigate class="text-xs font-semibold text-zinc-700 underline underline-offset-2 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No accounting codes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $accountingCodes->links() }}
        </div>
    </div>
</div>
