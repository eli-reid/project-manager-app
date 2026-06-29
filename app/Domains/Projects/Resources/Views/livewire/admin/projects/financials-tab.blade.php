<div class="space-y-4">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Budget</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $financialSummary['budget'] !== null ? '$'.number_format($financialSummary['budget'], 2) : '—' }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Invoiced</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                ${{ number_format($financialSummary['invoiced'], 2) }}
                <span class="ml-1 text-xs text-zinc-400 dark:text-zinc-500">({{ $financialSummary['invoice_count'] }} {{ Str::plural('invoice', $financialSummary['invoice_count']) }})</span>
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Estimated Labor Cost</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                ${{ number_format($financialSummary['labor_cost'], 2) }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Remaining Budget</p>
            <p class="mt-2 text-sm font-medium {{ $financialSummary['remaining'] !== null && $financialSummary['remaining'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $financialSummary['remaining'] !== null ? '$'.number_format($financialSummary['remaining'], 2) : '—' }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Budget Used</p>
            <p class="mt-2 text-sm font-medium {{ $financialSummary['variance_pct'] !== null && $financialSummary['variance_pct'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $financialSummary['variance_pct'] !== null ? $financialSummary['variance_pct'].'%' : '—' }}
            </p>
        </div>
    </div>

    @if ($timecardSummary !== null)
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Hours Logged</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($timecardSummary['total_hours'], 2) }}h</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Regular Hours</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($timecardSummary['regular_hours'], 2) }}h</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Overtime Hours</p>
                <p class="mt-2 text-sm font-medium {{ $timecardSummary['overtime_hours'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($timecardSummary['overtime_hours'], 2) }}h</p>
            </div>
        </div>
    @endif
</div>
