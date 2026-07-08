<div class="bg-white dark:bg-slate-800 shadow rounded p-4">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">5 Day Forecast
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
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 min-w-full">
            @foreach($forecast as $day)
                <div class="bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded p-3 text-center flex flex-col justify-between items-center h-48">
                    <div class="w-full flex items-start justify-between">
                        @php
                            $displayDate = '-';
                            if (!empty($day['date'])) {
                                try {
                                    $displayDate = \Carbon\Carbon::parse($day['date'])->format('M j');
                                } catch (\Throwable $e) {
                                    $displayDate = $day['date'];
                                }
                            }
                        @endphp
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-100">{{ $displayDate }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-300 truncate text-right" style="max-width:50%">{{ $day['location_name'] ?? '' }}</div>
                    </div>

                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 text-zinc-500 dark:text-zinc-300 flex items-center justify-center">
                            @if(!empty($day['flux_icon']))
                                @php $iconName = $day['flux_icon']; @endphp
                                @if(\Illuminate\Support\Facades\View::exists('flux.icons.' . $iconName))
                                    {!! view('flux.icons.' . $iconName, ['attributes' => new \Illuminate\View\ComponentAttributeBag([])])->render() !!}
                                @else
                                    @php
                                        $base = explode('-', $iconName)[0] ?? $iconName;
                                        $emoji = match ($base) {
                                            'cloud', 'rain', 'snow' => '☁️',
                                            'bolt', 'lightning' => '⚡️',
                                            'sun', 'clear' => '☀️',
                                            default => '🌤️',
                                        };
                                    @endphp
                                    <span aria-hidden="true">{{ $emoji }}</span>
                                @endif
                            @endif
                        </div>

                        <div class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ isset($day['temperature']) ? round($day['temperature']).'°F' : '—' }}</div>
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
