@php
    $badgeClasses = match ($status) {
        \App\Domains\Timecards\Models\Timecard::STATUS_SUBMITTED => 'text-amber-300 border-amber-800/60 bg-amber-950/30',
        \App\Domains\Timecards\Models\Timecard::STATUS_APPROVED => 'text-emerald-300 border-emerald-800/60 bg-emerald-950/30',
        \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED => 'text-red-300 border-red-800/60 bg-red-950/30',
        default => 'text-zinc-300 border-zinc-700 bg-zinc-800',
    };
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $badgeClasses }}">
    {{ str($status)->replace('-', ' ')->headline() }}
</span>
