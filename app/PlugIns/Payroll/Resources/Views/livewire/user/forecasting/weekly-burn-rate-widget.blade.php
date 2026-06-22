<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-1">
            <flux:heading size="lg">{{ __('Project Payroll Forecast') }}</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Track projected labor burn for this project using current payroll activity and rate assumptions.') }}
            </flux:text>
        </div>

        <flux:button size="sm" variant="ghost" :href="route('reports.payroll.forecasting.index')" icon="chart-bar" wire:navigate>
            {{ __('Open Full Forecasting') }}
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Projected Remaining Cost') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $projectForecast !== null ? '$'.number_format((float) $projectForecast['total_remaining_cost'], 2) : '—' }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Based on remaining budgeted project hours and current blended labor rates.') }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Estimated Weeks Remaining') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $projectForecast !== null ? number_format((float) $projectForecast['weeks_remaining'], 2) : '—' }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Calculated from recent project hours and remaining labor budget.') }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Blended Rate') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $projectForecast !== null ? '$'.number_format((float) $projectForecast['blended_rate'], 2) : '—' }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $projectPayRateTypeName ? __('Project rate type: :type', ['type' => $projectPayRateTypeName]) : __('No project pay rate type is assigned.') }}
            </p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Org Weekly Burn') }}</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $trailingForecast !== null ? '$'.number_format((float) $trailingForecast['weekly_cost'], 2) : '—' }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Trailing average across payroll statements for quick trend comparison.') }}
            </p>
        </article>
    </div>

    @if ($projectForecast === null)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('Project-specific forecasting is unavailable until this project has payroll budget hours configured and payroll history associated with the project.') }}
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">{{ __('Project Forecast Details') }}</flux:heading>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Budget Hours') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $projectForecast['budget_hours'], 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actual Hours To Date') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $projectForecast['actual_hours_to_date'], 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Remaining Hours') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $projectForecast['remaining_hours'], 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Projected Weekly Cost') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $projectForecast['weekly_cost'], 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Weeks Remaining') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $projectForecast['weeks_remaining'], 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Remaining Cost') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $projectForecast['total_remaining_cost'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">{{ __('Payroll Trend Baseline') }}</flux:heading>

                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Trailing Weekly Cost') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) ($trailingForecast['weekly_cost'] ?? 0), 2) }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Observed over :weeks payroll week(s).', ['weeks' => $trailingForecast['based_on_weeks'] ?? 0]) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Headcount Forecast') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) ($headcountForecast['weekly_forecast'] ?? 0), 2) }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Across :count active employee(s) at an average rate of $:rate/hr.', ['count' => $headcountForecast['employees_active'] ?? 0, 'rate' => number_format((float) ($headcountForecast['avg_rate'] ?? 0), 2)]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>