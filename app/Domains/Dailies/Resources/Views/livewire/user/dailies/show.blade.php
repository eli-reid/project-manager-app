<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Daily Report Details') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dailies.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>{{ __('Back') }}</a>

            @can('update', $dailyReport)
                <a href="{{ route('dailies.edit', $dailyReport) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>{{ __('Edit') }}</a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm md:grid-cols-2 dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Status') }}:</span> {{ str($dailyReport->status)->headline() }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Date') }}:</span> {{ optional($dailyReport->report_date)->format('M j, Y') }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Project') }}:</span> {{ $dailyReport->project?->name ?? ($dailyReport->custom_project_name ?: '—') }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Submitted By') }}:</span> {{ trim(($dailyReport->submittedBy?->first_name ?? '').' '.($dailyReport->submittedBy?->last_name ?? '')) ?: '—' }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Regular Hours') }}:</span> {{ number_format((float) $dailyReport->total_regular_hours, 2) }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Overtime Hours') }}:</span> {{ number_format((float) $dailyReport->total_overtime_hours, 2) }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Total Hours') }}:</span> {{ number_format((float) $dailyReport->total_hours, 2) }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Weather') }}:</span> {{ $dailyReport->weather_condition ?: '—' }}</p>

        @if ($dailyReport->temperature !== null)
            <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Temperature') }}:</span> {{ number_format((float) $dailyReport->temperature, 1) }} {{ $dailyReport->temperature_unit }}</p>
        @endif

        @if (filled($dailyReport->additional_notes))
            <p class="text-sm text-zinc-600 dark:text-zinc-300 md:col-span-2">
                <span class="font-semibold">{{ __('Notes') }}:</span>
                <span class="whitespace-pre-wrap">{{ $dailyReport->additional_notes }}</span>
            </p>
        @endif

        @if (filled($dailyReport->rejection_reason))
            <p class="text-sm text-rose-700 dark:text-rose-300 md:col-span-2">
                <span class="font-semibold">{{ __('Rejection Reason') }}:</span>
                <span class="whitespace-pre-wrap">{{ $dailyReport->rejection_reason }}</span>
            </p>
        @endif
    </div>

    @php
        $sections = [
            ['key' => 'work_performed',  'label' => 'Work Performed',  'empty' => 'No work items recorded.'],
            ['key' => 'materials_used',  'label' => 'Materials Used',  'empty' => 'No materials recorded.'],
            ['key' => 'equipment_used',  'label' => 'Equipment Used',  'empty' => 'No equipment recorded.'],
            ['key' => 'safety_issues',   'label' => 'Safety Issues',   'empty' => 'No safety issues reported.'],
            ['key' => 'delays',          'label' => 'Delays',          'empty' => 'No delays recorded.'],
            ['key' => 'visitors',        'label' => 'Visitors',        'empty' => 'No visitors recorded.'],
            ['key' => 'onsite_employees','label' => 'Employees Onsite','empty' => 'No onsite employees recorded.'],
        ];
    @endphp

    @foreach ($sections as $section)
        @php $items = $dailyReport->{$section['key']} ?? []; @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __($section['label']) }}</h3>
            @if (count($items) > 0)
                <ul class="space-y-1">
                    @foreach ($items as $item)
                        <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                            @if ($section['key'] === 'work_performed' && is_array($item))
                                <span>
                                    {{ $item['description'] ?? '—' }}
                                    @if (filled($item['hours'] ?? null))
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ number_format((float) $item['hours'], 2) }} {{ __('hrs') }})</span>
                                    @endif
                                    @if (($item['is_overtime'] ?? false) === true)
                                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">[{{ __('OT') }}]</span>
                                    @endif
                                    @if (! empty($item['employees'] ?? []))
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">- {{ implode(', ', $item['employees']) }}</span>
                                    @endif
                                </span>
                            @else
                                {{ is_array($item) ? ($item['description'] ?? '—') : $item }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm italic text-zinc-400">{{ __($section['empty']) }}</p>
            @endif
        </div>
    @endforeach
</div>
