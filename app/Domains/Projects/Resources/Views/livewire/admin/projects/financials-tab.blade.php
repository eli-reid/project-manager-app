<div class="space-y-4">
    @php
        $paymentReceiptDelta = $financialSummary['payment_receipt_delta'];
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-8">
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
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Payments Received</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                ${{ number_format($financialSummary['payments_received'], 2) }}
                <span class="ml-1 text-xs text-zinc-400 dark:text-zinc-500">({{ $financialSummary['payment_receipt_count'] }} {{ Str::plural('receipt', $financialSummary['payment_receipt_count']) }})</span>
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Spent</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                ${{ number_format($financialSummary['spent_total'], 2) }}
            </p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Invoices + estimated labor</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Recs +/-</p>
            <p class="mt-2 text-sm font-medium {{ $paymentReceiptDelta < 0 ? 'text-red-600 dark:text-red-400' : ($paymentReceiptDelta > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-zinc-100') }}">
                {{ $paymentReceiptDelta > 0 ? '+$' : ($paymentReceiptDelta < 0 ? '-$' : '$') }}{{ number_format(abs($paymentReceiptDelta), 2) }}
            </p>
            <p class="mt-1 text-xs {{ $paymentReceiptDelta < 0 ? 'text-red-500 dark:text-red-400' : ($paymentReceiptDelta > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400') }}">
                @if ($paymentReceiptDelta > 0)
                    Receipts exceed tracked spend.
                @elseif ($paymentReceiptDelta < 0)
                    Receipts trail tracked spend.
                @else
                    Receipts match tracked spend.
                @endif
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

    @if ($canViewPaymentReceipts)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Pay Recs live in their own feature domain.</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use the dedicated project tab to manage receipt entries while using the summary above to compare spend against collections.</p>
                </div>
                <a href="{{ app(\App\Domains\Projects\Services\ProjectTabLinkBuilder::class)->to($project, 'payment-receipts') }}" wire:navigate class="inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Open Pay Recs
                </a>
            </div>
        </div>
    @endif
    </div>
</div>
