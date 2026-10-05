@php
    $isToday = fn ($date) => $date->isSameDay(now());
@endphp

{{-- Actions live inside the component root: the layout's headerAction slot renders outside Livewire, so wire:click there would not work. --}}
<div @class(['flex flex-col gap-4 px-4 py-5', 'pb-44' => $canEdit || $canSubmit, 'pb-28' => ! ($canEdit || $canSubmit)])>
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @foreach (['entries', 'timecard'] as $errorKey)
        @error($errorKey)
            <div class="rounded-2xl border border-red-800/60 bg-red-950/40 px-4 py-3 text-sm font-medium text-red-200">{{ $message }}</div>
        @enderror
    @endforeach

    {{-- Week summary --}}
    <div class="flex items-center justify-between gap-3 rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-zinc-100">
                {{ $timecard->week_starting->format('M j') }} &ndash; {{ $timecard->week_ending->format('M j, Y') }}
            </p>
            <div class="mt-1.5">
                @include('timecards::livewire.mobile.timecards.partials.status-badge', ['status' => $timecard->status])
            </div>
        </div>
        <div class="text-right">
            <p class="text-2xl font-semibold tabular-nums text-zinc-50">{{ number_format((float) $timecard->total_hours, 2) }}</p>
            <p class="text-[11px] uppercase tracking-[0.16em] text-zinc-500">{{ __('Total hrs') }}</p>
        </div>
    </div>

    @if ($canReset)
        <button
            type="button"
            wire:click="resetToDraft"
            wire:confirm="{{ __('Reset this timecard to draft so it can be edited?') }}"
            wire:loading.attr="disabled"
            wire:target="resetToDraft"
            class="flex min-h-12 w-full items-center justify-center rounded-2xl border border-zinc-700 text-sm font-semibold text-zinc-200 active:bg-zinc-800 disabled:opacity-60"
            data-mobile-haptic
        >
            <span wire:loading.remove wire:target="resetToDraft">{{ __('Reset to Draft') }}</span>
            <span wire:loading wire:target="resetToDraft">{{ __('Resetting…') }}</span>
        </button>
    @endif

    @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED && $timecard->rejection_reason)
        <div class="rounded-2xl border border-red-800/60 bg-red-950/30 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-red-400">{{ __('Rejection Reason') }}</p>
            <p class="mt-1 text-sm text-red-100">{{ $timecard->rejection_reason }}</p>
        </div>
    @endif

    @if ($timecard->notes)
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes') }}</p>
            <p class="mt-1 text-sm text-zinc-300">{{ $timecard->notes }}</p>
        </div>
    @endif

    {{-- Entries by day --}}
    <div class="flex flex-col gap-3">
        @foreach ($days as $day)
            <section wire:key="day-{{ $day['date']->toDateString() }}" class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
                <div class="flex min-h-12 items-center justify-between gap-3 px-4 py-2">
                    <p class="text-sm font-semibold text-zinc-100">
                        {{ $day['date']->format('l') }}
                        <span class="ml-1 font-normal text-zinc-500">{{ $day['date']->format('M j') }}</span>
                        @if ($isToday($day['date']))
                            <span class="ml-1 rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-900">{{ __('Today') }}</span>
                        @endif
                    </p>

                    <div class="flex items-center gap-2">
                        <span @class([
                            'text-sm tabular-nums',
                            'font-semibold text-zinc-100' => $day['hours'] > 0,
                            'text-zinc-600' => $day['hours'] <= 0,
                        ])>{{ number_format($day['hours'], 2) }}h</span>

                        @if ($canEdit)
                            <a
                                href="{{ route('timecards.mobile.entries.create', ['timecard' => $timecard, 'date' => $day['date']->toDateString()]) }}"
                                wire:navigate
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-zinc-700 text-zinc-300 active:bg-zinc-800"
                                aria-label="{{ __('Add entry for :day', ['day' => $day['date']->format('l')]) }}"
                                data-mobile-haptic
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                            </a>
                        @endif
                    </div>
                </div>

                @if ($day['entries']->isNotEmpty())
                    <div class="divide-y divide-zinc-800 border-t border-zinc-800">
                        @foreach ($day['entries'] as $entry)
                            @php
                                $leaveCategory = $entry->project?->leave_category;
                                $startLabel = $entry->start_time
                                    ? \Illuminate\Support\Carbon::createFromFormat('H:i', substr((string) $entry->start_time, 0, 5))->format('g:i A')
                                    : null;
                            @endphp

                            <{{ $canEdit ? 'a' : 'div' }}
                                @if ($canEdit)
                                    href="{{ route('timecards.mobile.entries.edit', ['timecard' => $timecard, 'entry' => $entry]) }}"
                                    wire:navigate
                                    data-mobile-haptic
                                @endif
                                wire:key="entry-{{ $entry->id }}"
                                @class(['flex items-center gap-3 px-4 py-3', 'active:bg-zinc-800' => $canEdit])
                            >
                                <span @class([
                                    'h-8 w-1 shrink-0 rounded-full',
                                    'bg-emerald-500' => $leaveCategory === 'sick',
                                    'bg-sky-500' => $leaveCategory === 'vacation',
                                    'bg-zinc-600' => ! $leaveCategory,
                                ])></span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-100">
                                        {{ $entry->project?->name ?? $entry->custom_project_name ?? __('Unassigned') }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-zinc-500">
                                        {{ $startLabel ?? __('No start time') }}
                                        @if ($entry->costCode)
                                            &middot; {{ $entry->costCode->code }}
                                        @endif
                                        @if ($entry->notes)
                                            &middot; {{ $entry->notes }}
                                        @endif
                                    </p>
                                </div>

                                <p class="shrink-0 text-sm font-semibold tabular-nums text-zinc-100">{{ number_format((float) $entry->hours, 2) }}h</p>

                                @if ($canEdit)
                                    <svg class="h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </{{ $canEdit ? 'a' : 'div' }}>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    @if ($canEdit || $canSubmit)
        <div class="pointer-events-none fixed inset-x-0 bottom-20 z-40">
            <div class="mx-auto flex max-w-md gap-3 px-4">
                @if ($canSubmit)
                    <button
                        type="button"
                        wire:click="submit"
                        wire:confirm="{{ __('Submit this timecard for approval? You won\'t be able to edit it afterwards.') }}"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        @class([
                            'pointer-events-auto flex min-h-14 items-center justify-center rounded-2xl border border-zinc-700 bg-zinc-900 px-4 text-sm font-semibold text-zinc-100 shadow-lg active:bg-zinc-800 disabled:opacity-60',
                            'flex-1' => ! $canEdit,
                            'w-2/5' => $canEdit,
                        ])
                        data-mobile-haptic
                    >
                        <span wire:loading.remove wire:target="submit">{{ __('Submit') }}</span>
                        <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
                    </button>
                @endif

                @if ($canEdit)
                    <a
                        href="{{ route('timecards.mobile.entries.create', $timecard) }}"
                        wire:navigate
                        class="pointer-events-auto flex min-h-14 flex-1 items-center justify-center gap-2 rounded-2xl bg-zinc-100 text-base font-semibold text-zinc-900 shadow-lg active:bg-zinc-300"
                        data-mobile-haptic
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                        {{ __('Add Entry') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
