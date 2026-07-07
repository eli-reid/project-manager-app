<div class="bg-white shadow rounded p-4">
    <h3 class="text-lg font-medium">5 Day Forecast</h3>

    @if(empty($forecast))
        <p class="text-sm text-gray-500">No forecast available. Configure a default location in settings.</p>
    @else
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-3">
            @foreach($forecast as $day)
                <div class="border rounded p-2 text-center">
                    <div class="text-sm font-semibold">{{ $day['date'] ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $day['location_name'] ?? '' }}</div>
                    <div class="mt-2 text-2xl">{{ isset($day['temperature']) ? round($day['temperature']).'°F' : '—' }}</div>
                    <div class="text-sm text-gray-600">{{ $day['condition'] ?? 'N/A' }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
