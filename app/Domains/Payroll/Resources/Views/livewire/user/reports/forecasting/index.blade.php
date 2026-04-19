<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-1">
        <flux:heading size="xl">{{ __('Payroll Forecasting') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Compare trailing payroll burn, active headcount forecast, and weekly variance for short-range staffing planning.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="w-40">
                <flux:label>{{ __('Trailing Weeks') }}</flux:label>
                <flux:select wire:model.live="trailingWeeks">
                    @foreach ([2, 4, 6, 8, 12] as $weeks)
                        <option value="{{ $weeks }}">{{ $weeks }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="self-end">
                <flux:checkbox wire:model.live="includeOvertime" :label="__('Include overtime in trailing cost')" />
            </flux:field>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Trailing Weekly Burn') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                ${{ number_format((float) ($summary['trailing_average']['weekly_cost'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Observed over :weeks payroll week(s).', ['weeks' => $summary['trailing_average']['based_on_weeks'] ?? 0]) }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Trailing Total Cost') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                ${{ number_format((float) ($summary['trailing_average']['total_cost'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Last observed week ending :date.', ['date' => $summary['trailing_average']['last_week'] ?? '—']) }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Headcount Weekly Forecast') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                ${{ number_format((float) ($summary['headcount']['weekly_forecast'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Across :count active employee(s) at an average rate of $:rate/hr.', ['count' => $summary['headcount']['employees_active'] ?? 0, 'rate' => number_format((float) ($summary['headcount']['avg_rate'] ?? 0), 2)]) }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Variance') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                ${{ number_format((float) ($summary['variance']['variance'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ number_format((float) ($summary['variance']['variance_percent'] ?? 0), 2) }}% {{ __('vs headcount forecast') }}
            </p>
        </article>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Trailing Weeks Setting') }}</th>
                        <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-200">{{ $trailingWeeks }}</td>
                    </tr>
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Include Overtime') }}</th>
                        <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-200">{{ $includeOvertime ? __('Yes') : __('No') }}</td>
                    </tr>
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Monthly Headcount Forecast') }}</th>
                        <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-200">${{ number_format((float) ($summary['headcount']['monthly_forecast'] ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Variance Category') }}</th>
                        <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-200">{{ ucfirst((string) ($summary['variance']['category'] ?? 'neutral')) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('How To Use This Forecast') }}</flux:heading>
            <div class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                <p>{{ __('Use trailing burn to understand recent payroll pace from real pay statements.') }}</p>
                <p>{{ __('Use headcount forecast to estimate upcoming weekly payroll based on active employee rates.') }}</p>
                <p>{{ __('Use variance to spot whether recent payroll cost is running above or below the staffing-based expectation.') }}</p>
            </div>
        </div>
    </div>
</section>