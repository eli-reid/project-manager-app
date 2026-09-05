<div class="bg-white dark:bg-slate-800 shadow rounded p-4">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">3 Day Forecast
        @if(!empty($forecast) && isset($forecast[0]['location_name']) && trim($forecast[0]['location_name']) !== '')
            - {{ $forecast[0]['location_name'] }}
        @elseif(!empty($location))
            - {{ $location }}
        @endif
    </h3>

    @if(empty($forecast))
        <p class="text-sm text-gray-500 dark:text-gray-300">No forecast available. Configure a default location in settings.</p>
    @else
        <div class="mt-3 overflow-x-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 min-w-full">
            @foreach($forecast as $day)
                <div class="bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded p-3 text-center flex flex-col justify-between items-center h-48">
                    <div class="w-full flex items-start justify-between">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-100">{{ $day['display_date'] ?? '-' }}</div>
                    </div>

                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 text-zinc-500 dark:text-zinc-300 flex items-center justify-center">{!! $day['icon_html'] ?? '' !!}</div>

                        <div class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ isset($day['temperature']) ? round($day['temperature']).'°F' : '—' }}</div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            H {{ isset($day['temperature_high']) ? round($day['temperature_high']).'°' : '—' }}
                            /
                            L {{ isset($day['temperature_low']) ? round($day['temperature_low']).'°' : '—' }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">{{ $day['condition'] ?? 'N/A' }}</div>
                    </div>

                    <div class="w-full text-center">
                        <!-- reserved footer space -->
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    @endif
</div>
