<div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Weekly Hour Adjustment Report</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Review weekly hour overrides. These entries are tracked separately and do not modify timecards.
            </p>
        </div>
        <div class="flex gap-2">
            <a
                href="{{ route('admin.payroll.reports.weekly-employee-hours', ['week_start' => $weekStart]) }}"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                wire:navigate
            >
                Back to Weekly Employee Hours
            </a>
            <button
                onclick="window.print()"
                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
            >
                Print
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Year</label>
            <select
                wire:model.live="year"
                class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
            >
                @foreach ($this->availableYears as $availableYear)
                    <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Employee</label>
            <select
                wire:model.live="employeeId"
                class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
            >
                <option value="all">All Employees</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="space-y-2 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30">
        <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
            <strong>
                {{ $year }} Adjustment Summary
                @if ($employeeId !== 'all')
                    for {{ $this->employees->firstWhere('id', $employeeId)?->name ?? 'Selected Employee' }}
                @endif
            </strong>
        </p>
        <p class="text-sm text-blue-800 dark:text-blue-300">
            Total adjustments: <strong>{{ $this->adjustments->count() }}</strong>
            | Source total: <strong>{{ number_format($this->totalSourceHours, 2) }}</strong> hours
            | Adjusted total: <strong>{{ number_format($this->totalAdjustedHours, 2) }}</strong> hours
            | Net delta: <strong>{{ number_format($this->totalDelta, 2) }}</strong> hours
        </p>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Week Starting</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Source</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Adjusted</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Delta</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Edited By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Edited At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->adjustments as $adjustment)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $adjustment->week_start?->format('M j, Y') ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $adjustment->employee?->name ?? $adjustment->user_id }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $adjustment->source_hours, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ number_format((float) $adjustment->adjusted_hours, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $adjustment->adjusted_hours - (float) $adjustment->source_hours, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $adjustment->reason }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $adjustment->editor?->name ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $adjustment->edited_at?->format('M j, Y g:i A') ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No manual hour adjustments recorded for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-xs text-zinc-500 dark:text-zinc-400 print:hidden">
        <p>Generated on {{ now()->format('M j, Y \a\t g:i A') }}</p>
    </div>
</div>

<style>
    @media print {
        .print\:hidden {
            display: none;
        }

        body {
            background: white;
        }

        * {
            box-shadow: none !important;
        }
    }
</style>
