<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Payroll Forecasting</h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Project future labor costs and analyze variances</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500 dark:text-slate-400">Report Period</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ now()->format('M Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Trailing Average Weeks
            </label>
            <select wire:model="trailingWeeks" wire:change="updateTrailingWeeks" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="2">2 weeks</option>
                <option value="4">4 weeks (default)</option>
                <option value="8">8 weeks</option>
                <option value="12">12 weeks</option>
            </select>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <input type="checkbox" wire:model="includeOvertime" wire:change="updateIncludeOvertime" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                <span class="ml-2">Include Overtime in Forecast</span>
            </label>
        </div>
    </div>

    <!-- Summary Section -->
    <wire:loading wire:target="updateTrailingWeeks,updateIncludeOvertime">
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-blue-700 dark:text-blue-200">Recalculating forecasts...</span>
        </div>
    </wire:loading>

    <!-- Forecast Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Trailing Average Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 dark:text-white">Trailing Average</h3>
                <span class="text-2xl">📊</span>
            </div>
            @if ($summary && isset($summary['trailing_average']))
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Weekly Cost</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ currency($summary['trailing_average']['weekly_cost'] ?? 0) }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Based on {{ $summary['trailing_average']['based_on_weeks'] ?? 4 }} weeks
                    </p>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400 text-sm">No data available</p>
            @endif
        </div>

        <!-- Headcount Forecast Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 dark:text-white">Headcount Based</h3>
                <span class="text-2xl">👥</span>
            </div>
            @if ($summary && isset($summary['headcount']))
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Active Employees</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $summary['headcount']['employees_active'] ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Avg rate: {{ currency($summary['headcount']['avg_rate'] ?? 0) }}/hr
                    </p>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400 text-sm">No data available</p>
            @endif
        </div>

        <!-- Variance Analysis Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 dark:text-white">Variance Status</h3>
                <span class="text-2xl">⚖️</span>
            </div>
            @if ($summary && isset($summary['variance']))
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Category</p>
                    <p class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-medium 
                        @if ($summary['variance']['category'] === 'favorable')
                            bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200
                        @elseif ($summary['variance']['category'] === 'unfavorable')
                            bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                        @else
                            bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200
                        @endif
                    ">
                        {{ ucfirst($summary['variance']['category']) }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        {{ abs($summary['variance']['variance_percent'] ?? 0) }}% variance
                    </p>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400 text-sm">No data available</p>
            @endif
        </div>
    </div>

    <!-- Detailed Sections (collapsible) -->
    <div class="space-y-4">
        <!-- Trailing Average Details -->
        <details class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden group">
            <summary class="px-6 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-900 dark:text-white flex items-center justify-between">
                <span>Trailing Average Forecast Details</span>
                <svg class="w-5 h-5 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </summary>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                @if ($summary && isset($summary['trailing_average']))
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Weekly Cost</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['trailing_average']['weekly_cost'] ?? 0) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Total (rolling)</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['trailing_average']['total_cost'] ?? 0) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Period</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ $summary['trailing_average']['based_on_weeks'] ?? 4 }} weeks
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Last Week</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['trailing_average']['last_week'] ?? 0) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-slate-500 dark:text-slate-400">No data available</p>
                @endif
            </div>
        </details>

        <!-- Headcount Forecast Details -->
        <details class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden group">
            <summary class="px-6 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-900 dark:text-white flex items-center justify-between">
                <span>Headcount Forecast Details</span>
                <svg class="w-5 h-5 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </summary>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                @if ($summary && isset($summary['headcount']))
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Active Employees</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ $summary['headcount']['employees_active'] ?? 0 }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Average Rate</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['headcount']['avg_rate'] ?? 0) }}/hr
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Weekly Forecast</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['headcount']['weekly_forecast'] ?? 0) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Monthly Forecast</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['headcount']['monthly_forecast'] ?? 0) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-slate-500 dark:text-slate-400">No data available</p>
                @endif
            </div>
        </details>

        <!-- Variance Analysis Details -->
        <details class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden group">
            <summary class="px-6 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-900 dark:text-white flex items-center justify-between">
                <span>Variance Analysis Details</span>
                <svg class="w-5 h-5 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </summary>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                @if ($summary && isset($summary['variance']))
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Variance Amount</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ currency($summary['variance']['variance'] ?? 0) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-600 dark:text-slate-400">Variance Percent</dt>
                            <dd class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                {{ $summary['variance']['variance_percent'] ?? 0 }}%
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm text-slate-600 dark:text-slate-400 mb-2">Category</dt>
                            <dd class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                                @if ($summary['variance']['category'] === 'favorable')
                                    bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200
                                @elseif ($summary['variance']['category'] === 'unfavorable')
                                    bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                @else
                                    bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200
                                @endif
                            ">
                                {{ ucfirst($summary['variance']['category']) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-slate-500 dark:text-slate-400">No data available</p>
                @endif
            </div>
        </details>
    </div>

    <!-- Footer Info -->
    <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4">
        <p class="text-xs text-slate-600 dark:text-slate-400">
            💡 <strong>Note:</strong> Forecasts are based on historical payroll data and current employee rates. 
            Seasonal adjustments require at least 2 years of data. Update your forecasting settings for more accurate projections.
        </p>
    </div>
</div>
