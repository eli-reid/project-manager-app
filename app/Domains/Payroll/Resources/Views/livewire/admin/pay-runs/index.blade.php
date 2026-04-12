<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Payroll Pay Runs</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Review run status and open runs for approval and finalization workflow.</p>
        </div>

        <a href="{{ route('admin.payroll.runs.create') }}" wire:navigate class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
            Create Preview Run
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-3 sm:max-w-xs">
        <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="preview">Preview</option>
            <option value="approved">Approved</option>
            <option value="finalized">Finalized</option>
            <option value="void">Void</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Period</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employees</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Net</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($runs as $run)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ optional($run->pay_period_start)->format('M j, Y') }} - {{ optional($run->pay_period_end)->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ optional($run->pay_date)->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $run->status->label() }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ (int) $run->employee_count }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $run->total_gross, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $run->total_net, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.payroll.runs.show', $run) }}" wire:navigate class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No pay runs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($runs->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $runs->links() }}
            </div>
        @endif
    </div>
</div>
