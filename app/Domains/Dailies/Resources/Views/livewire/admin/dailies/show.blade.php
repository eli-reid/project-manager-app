<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Daily Report Details') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $backUrl !== '' ? $backUrl : route('admin.dailies.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Back') }}</a>
            @can('update', $dailyReport)
                <a href="{{ route('admin.dailies.edit', $dailyReport) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Edit') }}</a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @error('dailyReport')
        <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ $message }}</div>
    @enderror

    {{-- Approval action banner --}}
    @if ($dailyReport->status === \App\Domains\Dailies\Models\DailyReport::STATUS_SUBMITTED)
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm dark:border-blue-700 dark:bg-blue-900/20">
            <p class="mb-3 text-sm font-semibold text-blue-800 dark:text-blue-300">{{ __('This report is awaiting review.') }}</p>

            <div class="flex flex-wrap gap-3">
                @can('approve', $dailyReport)
                    <button
                        type="button"
                        wire:click="approve"
                        wire:confirm="{{ __('Approve this daily report?') }}"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        {{ __('Approve') }}
                    </button>
                @endcan
            </div>

            @can('reject', $dailyReport)
                <div class="mt-4 space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">{{ __('Rejection Reason') }}</label>
                    <textarea
                        wire:model="rejectionReason"
                        rows="3"
                        placeholder="{{ __('Explain why this report is being rejected...') }}"
                        class="w-full rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none dark:border-blue-700 dark:bg-zinc-950 dark:text-zinc-100"
                    ></textarea>
                    @error('rejectionReason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <button
                        type="button"
                        wire:click="reject"
                        class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                    >
                        {{ __('Reject Report') }}
                    </button>
                </div>
            @endcan
        </div>
    @elseif ($dailyReport->status === \App\Domains\Dailies\Models\DailyReport::STATUS_APPROVED)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ __('This report has been approved.') }}
        </div>
    @elseif ($dailyReport->status === \App\Domains\Dailies\Models\DailyReport::STATUS_REJECTED)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-700 dark:bg-rose-900/20">
            <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">{{ __('This report was rejected.') }}</p>
            @if (filled($dailyReport->rejection_reason))
                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $dailyReport->rejection_reason }}</p>
            @endif
        </div>
    @endif

    {{-- Info grid --}}
    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm md:grid-cols-2 dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Status') }}:</span> {{ str($dailyReport->status)->headline() }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Date') }}:</span> {{ optional($dailyReport->report_date)->format('M j, Y') }}</p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="font-semibold">{{ __('Worker') }}:</span> {{ trim(($dailyReport->user?->first_name ?? '').' '.($dailyReport->user?->last_name ?? '')) ?: '—' }}</p>
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
