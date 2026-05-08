@php
    $isDraft = $timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT;
    $isRejected = $timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED;

    $statusColor = match ($timecard->status) {
        \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT => 'text-zinc-300 border-zinc-700 bg-zinc-800',
        \App\Domains\Timecards\Models\Timecard::STATUS_SUBMITTED => 'text-amber-300 border-amber-800/60 bg-amber-950/30',
        \App\Domains\Timecards\Models\Timecard::STATUS_APPROVED => 'text-emerald-300 border-emerald-800/60 bg-emerald-950/30',
        \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED => 'text-red-300 border-red-800/60 bg-red-950/30',
        default => 'text-zinc-300 border-zinc-700 bg-zinc-800',
    };
@endphp

<div class="flex flex-col gap-4 px-4 py-5 pb-32">

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Week + Status header card --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Week') }}</p>
        <p class="mt-1 text-base font-semibold text-zinc-100">
            {{ optional($timecard->week_starting)->format('M j') }} &ndash; {{ optional($timecard->week_ending)->format('M j, Y') }}
        </p>
        <div class="mt-3 flex items-center gap-3">
            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ str($timecard->status)->replace('-', ' ')->headline() }}
            </span>
            <span class="text-xs text-zinc-400">
                {{ number_format((float) $timecard->total_hours, 2) }} {{ __('hrs total') }}
            </span>
        </div>
    </div>

    {{-- Leave Balances --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-emerald-900/60 bg-emerald-950/30 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-400">{{ __('Sick') }}</p>
            <p class="mt-1 text-lg font-semibold text-emerald-100">
                {{ number_format((float) data_get($leaveBalances, 'sick.remaining', 0), 2) }}
                <span class="text-sm font-normal text-emerald-300/70">{{ __('hrs') }}</span>
            </p>
            <p class="mt-0.5 text-[11px] text-emerald-300/60">
                {{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'sick.used', 0), 2) }} / {{ number_format((float) data_get($leaveBalances, 'sick.allowed', 0), 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-sky-900/60 bg-sky-950/30 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-400">{{ __('Vacation') }}</p>
            <p class="mt-1 text-lg font-semibold text-sky-100">
                {{ number_format((float) data_get($leaveBalances, 'vacation.remaining', 0), 2) }}
                <span class="text-sm font-normal text-sky-300/70">{{ __('hrs') }}</span>
            </p>
            <p class="mt-0.5 text-[11px] text-sky-300/60">
                {{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'vacation.used', 0), 2) }} / {{ number_format((float) data_get($leaveBalances, 'vacation.allowed', 0), 2) }}
            </p>
        </div>
    </div>

    {{-- Notes --}}
    @if ($timecard->notes)
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes') }}</p>
            <p class="mt-2 text-sm text-zinc-300">{{ $timecard->notes }}</p>
        </div>
    @endif

    {{-- Entries list --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-zinc-100">{{ __('Entries') }}</p>
            @if ($isDraft)
                <a
                    href="{{ route('timecards.mobile.edit', $timecard) }}"
                    class="text-xs font-semibold text-zinc-400 hover:text-zinc-200"
                    wire:navigate
                    data-mobile-haptic
                >{{ __('Edit') }}</a>
            @endif
        </div>

        @if ($timecard->entries->isEmpty())
            <p class="py-4 text-center text-sm text-zinc-500">{{ __('No entries added yet.') }}</p>
        @else
            <div class="flex flex-col divide-y divide-zinc-800">
                @foreach ($timecard->entries as $entry)
                    <div class="flex items-center justify-between py-3" wire:key="entry-{{ $entry->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-100">
                                {{ $entry->project?->name ?? $entry->custom_project_name ?? __('Unassigned') }}
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-400">
                                {{ optional($entry->date)->format('M j, Y') }}
                                @if ($entry->notes)
                                    &middot; {{ Str::limit($entry->notes, 40) }}
                                @endif
                            </p>
                        </div>
                        <p class="ml-3 shrink-0 text-sm font-semibold text-zinc-100">
                            {{ number_format((float) $entry->hours, 2) }} <span class="text-xs font-normal text-zinc-400">hrs</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Sticky action bar --}}
    @if ($isDraft || $isRejected)
        <div class="pointer-events-none fixed inset-x-0 bottom-16 z-40 px-4 pb-3 safe-area-bottom">
            <div class="pointer-events-auto flex gap-3 rounded-2xl border border-zinc-800 bg-zinc-950/95 p-3 shadow-xl backdrop-blur">
                @if ($isDraft)
                    <a
                        href="{{ route('timecards.mobile.edit', $timecard) }}"
                        class="flex flex-1 items-center justify-center rounded-xl border border-zinc-700 py-3 text-sm font-semibold text-zinc-200 active:bg-zinc-800"
                        wire:navigate
                        data-mobile-haptic
                    >
                        {{ __('Edit') }}
                    </a>
                    <button
                        type="button"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        class="flex flex-[2] items-center justify-center rounded-xl bg-white py-3 text-sm font-semibold text-zinc-900 active:bg-zinc-200 disabled:opacity-60"
                        data-mobile-haptic
                    >
                        <span wire:loading.remove wire:target="submit">{{ __('Submit Timecard') }}</span>
                        <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
                    </button>
                @endif

                @if ($isRejected)
                    <button
                        type="button"
                        wire:click="resetToDraft"
                        wire:loading.attr="disabled"
                        class="flex flex-1 items-center justify-center rounded-xl border border-zinc-700 py-3 text-sm font-semibold text-zinc-200 active:bg-zinc-800 disabled:opacity-60"
                        data-mobile-haptic
                    >
                        <span wire:loading.remove wire:target="resetToDraft">{{ __('Reset to Draft') }}</span>
                        <span wire:loading wire:target="resetToDraft">{{ __('Resetting…') }}</span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
