<div class="bg-white dark:bg-slate-800 shadow rounded p-4">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">5 Day Forecast</h3>

    @if(empty($forecast))
        <p class="text-sm text-gray-500 dark:text-gray-300">No forecast available. Configure a default location in settings.</p>
    @else
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-3">
            @foreach($forecast as $day)
                <div class="bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded p-2 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-100">{{ $day['date'] ?? '-' }}</div>
                        @if(!empty($day['flux_icon']))
                            <div class="text-zinc-500 dark:text-zinc-300">
                                <flux:icon :icon="$day['flux_icon']" class="size-4" />
                            </div>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-300">{{ $day['location_name'] ?? '' }}</div>
                    <div class="mt-2 text-2xl text-gray-900 dark:text-white">{{ isset($day['temperature']) ? round($day['temperature']).'°F' : '—' }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $day['condition'] ?? 'N/A' }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
