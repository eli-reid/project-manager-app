<section class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Pay Stub') }}</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ optional($stub->payRun?->pay_date)->format('F j, Y') ?? __('Pay Date Unavailable') }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('payroll.history') }}"
                wire:navigate
                class="inline-flex items-center rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                {{ __('Back to Stubs') }}
            </a>
            <button
                type="button"
                wire:click="downloadPdf"
                class="inline-flex items-center rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
            >
                {{ __('Download PDF') }}
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Gross Pay') }}</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->gross_pay, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Net Pay') }}</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->net_pay, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Regular Hours') }}</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $stub->total_regular_hours, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Overtime + Double Time') }}</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $stub->total_ot_hours + (float) $stub->total_dt_hours, 2) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Federal Tax') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->federal_tax, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('State Tax') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->state_tax, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Local Tax') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->local_tax, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Social Security') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->social_security, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Medicare') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->medicare, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Other Deductions') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->other_deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
