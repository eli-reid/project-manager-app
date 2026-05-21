<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Daily Report') }}</p>
                <p class="mt-1 text-sm text-zinc-300">{{ optional($dailyReport->report_date)->format('M j, Y') }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('dailies.mobile.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-800 px-3 text-xs font-semibold text-zinc-100 active:bg-zinc-700" wire:navigate data-mobile-haptic>
                    {{ __('Back') }}
                </a>

                @can('update', $dailyReport)
                    <a href="{{ route('dailies.mobile.edit', $dailyReport) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-800 px-3 text-xs font-semibold text-zinc-100 active:bg-zinc-700" wire:navigate data-mobile-haptic>
                        {{ __('Edit') }}
                    </a>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div class="mt-3 rounded-xl border border-emerald-700/40 bg-emerald-600/20 px-3 py-2 text-sm font-medium text-emerald-200">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="grid grid-cols-1 gap-2 text-sm text-zinc-300">
            <p><span class="font-semibold text-zinc-100">{{ __('Status') }}:</span> {{ str($dailyReport->status)->headline() }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Project') }}:</span> {{ $dailyReport->project?->name ?? ($dailyReport->custom_project_name ?: '—') }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Submitted By') }}:</span> {{ trim(($dailyReport->submittedBy?->first_name ?? '').' '.($dailyReport->submittedBy?->last_name ?? '')) ?: '—' }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Regular Hours') }}:</span> {{ number_format((float) $dailyReport->total_regular_hours, 2) }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Overtime Hours') }}:</span> {{ number_format((float) $dailyReport->total_overtime_hours, 2) }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Total Hours') }}:</span> {{ number_format((float) $dailyReport->total_hours, 2) }}</p>
            <p><span class="font-semibold text-zinc-100">{{ __('Weather') }}:</span> {{ $dailyReport->weather_condition ?: '—' }}</p>

            @if ($dailyReport->temperature !== null)
                <p><span class="font-semibold text-zinc-100">{{ __('Temperature') }}:</span> {{ number_format((float) $dailyReport->temperature, 1) }} {{ $dailyReport->temperature_unit }}</p>
            @endif

            @if (filled($dailyReport->additional_notes))
                <p>
                    <span class="font-semibold text-zinc-100">{{ __('Notes') }}:</span>
                    <span class="whitespace-pre-wrap">{{ $dailyReport->additional_notes }}</span>
                </p>
            @endif

            @if (filled($dailyReport->rejection_reason))
                <p class="text-rose-300">
                    <span class="font-semibold">{{ __('Rejection Reason') }}:</span>
                    <span class="whitespace-pre-wrap">{{ $dailyReport->rejection_reason }}</span>
                </p>
            @endif
        </div>
    </div>

    @php
        $sections = [
            ['key' => 'work_performed', 'label' => 'Work Performed', 'empty' => 'No work items recorded.'],
            ['key' => 'materials_used', 'label' => 'Materials Used', 'empty' => 'No materials recorded.'],
            ['key' => 'equipment_used', 'label' => 'Equipment Used', 'empty' => 'No equipment recorded.'],
            ['key' => 'safety_issues', 'label' => 'Safety Issues', 'empty' => 'No safety issues reported.'],
            ['key' => 'delays', 'label' => 'Delays', 'empty' => 'No delays recorded.'],
            ['key' => 'visitors', 'label' => 'Visitors', 'empty' => 'No visitors recorded.'],
            ['key' => 'onsite_employees', 'label' => 'Employees Onsite', 'empty' => 'No onsite employees recorded.'],
        ];
    @endphp

    @foreach ($sections as $section)
        @php $items = $dailyReport->{$section['key']} ?? []; @endphp
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __($section['label']) }}</h3>
            @if (count($items) > 0)
                <ul class="space-y-2">
                    @foreach ($items as $item)
                        <li class="rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-300">
                            @if ($section['key'] === 'work_performed' && is_array($item))
                                <p class="text-zinc-100">{{ $item['description'] ?? '—' }}</p>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-zinc-500">
                                    @if (filled($item['hours'] ?? null))
                                        <span>{{ number_format((float) $item['hours'], 2) }} {{ __('hrs') }}</span>
                                    @endif
                                    @if (($item['is_overtime'] ?? false) === true)
                                        <span class="font-semibold text-amber-300">{{ __('OT') }}</span>
                                    @endif
                                    @if (! empty($item['employees'] ?? []))
                                        <span>{{ implode(', ', $item['employees']) }}</span>
                                    @endif
                                </div>
                            @else
                                {{ is_array($item) ? ($item['description'] ?? '—') : $item }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm italic text-zinc-500">{{ __($section['empty']) }}</p>
            @endif
        </div>
    @endforeach
</div>
