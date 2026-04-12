<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Pay Run Details</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Period {{ optional($run->pay_period_start)->format('M j, Y') }} - {{ optional($run->pay_period_end)->format('M j, Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.payroll.runs.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Back to Runs
            </a>

            @can('payroll-runs.approve')
                @if ($run->status->value === 'preview')
                    <button wire:click="approve" type="button" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500" wire:loading.attr="disabled">
                        Approve
                    </button>
                @endif
            @endcan

            @can('payroll-runs.finalize')
                @if ($run->status->value === 'approved')
                    <button wire:click="finalize" type="button" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500" wire:loading.attr="disabled">
                        Finalize
                    </button>
                @endif
            @endcan

            @can('payroll-runs.void')
                @if ($run->status->value === 'finalized')
                    <button wire:click="voidRun" type="button" class="rounded-md bg-rose-700 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-600" wire:loading.attr="disabled">
                        Void Run
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @error('status')
        <div class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $message }}</div>
    @enderror

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $run->status->label() }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Date</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ optional($run->pay_date)->format('M j, Y') }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employees</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) $run->employee_count }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Gross</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $run->total_gross, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Net</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $run->total_net, 2) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Regular</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">OT</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">DT</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Taxes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($run->payrollStatements as $statement)
                        @php
                            $employeeName = trim((string) ($statement->user?->first_name ?? '').' '.(string) ($statement->user?->last_name ?? ''));
                            $taxTotal = (float) $statement->federal_tax + (float) $statement->state_tax + (float) $statement->local_tax + (float) $statement->social_security + (float) $statement->medicare;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ ($statement->payrollEmployeeProfile?->employee_number ? $statement->payrollEmployeeProfile->employee_number.' - ' : '').($employeeName !== '' ? $employeeName : 'Unknown Employee') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $statement->total_regular_hours, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $statement->total_ot_hours, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $statement->total_dt_hours, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $statement->gross_pay, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">${{ number_format($taxTotal, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $statement->net_pay, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No payroll statements were generated for this run.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
