<div class="space-y-4">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Weekly Burn Rate</h3>
            <span class="px-3 py-1 text-sm font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full">
                Forecasting
            </span>
        </div>

        <div wire:loading class="flex items-center justify-center py-8">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-slate-600 dark:text-slate-400">Loading forecast...</span>
            </div>
        </div>

        <div wire:loading.remove>
            @if ($forecast)
                <div class="space-y-3">
                    <div class="flex justify-between items-baseline">
                        <span class="text-slate-600 dark:text-slate-400 text-sm">Weekly Cost (avg)</span>
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ currency($forecast['weekly_cost'] ?? 0) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-baseline">
                        <span class="text-slate-600 dark:text-slate-400 text-sm">Based on {{ $forecast['based_on_weeks'] ?? 4 }} weeks</span>
                        <span class="text-slate-900 dark:text-white text-sm">
                            {{ $forecast['last_week'] ? 'Last week: ' . currency($forecast['last_week']) : 'N/A' }}
                        </span>
                    </div>

                    @if (isset($forecast['total_cost']))
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-baseline">
                                <span class="text-slate-600 dark:text-slate-400 text-sm">Total (4-week rolling)</span>
                                <span class="text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ currency($forecast['total_cost']) }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Last updated: {{ now()->format('M j, Y H:i') }}
                    </p>
                </div>
            @else
                <div class="py-8 text-center">
                    <p class="text-slate-500 dark:text-slate-400">
                        No forecast data available. Check your payroll configuration.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
