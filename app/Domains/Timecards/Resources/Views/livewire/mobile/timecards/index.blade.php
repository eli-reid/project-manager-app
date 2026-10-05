@php
    $canCreate = auth()->user()?->can('create', \App\Domains\Timecards\Models\Timecard::class) ?? false;
@endphp

<div class="flex flex-col gap-6 px-4 py-5 pb-28">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Pinned: this week + last week --}}
    <section class="flex flex-col gap-3">
        @foreach ($pinnedWeeks as $week)
            @php
                $timecard = $week['timecard'];
                $href = $timecard
                    ? route('timecards.mobile.show', $timecard)
                    : ($canCreate ? route('timecards.mobile.create', ['week_starting' => $week['start']->toDateString()]) : null);
            @endphp

            <a
                @if ($href) href="{{ $href }}" wire:navigate @endif
                wire:key="pinned-week-{{ $week['start']->toDateString() }}"
                @class([
                    'flex items-center justify-between gap-3 rounded-2xl border px-4 py-4',
                    'border-zinc-700 bg-zinc-900 active:bg-zinc-800' => $href,
                    'border-zinc-800 bg-zinc-900/60' => ! $href,
                ])
                data-mobile-haptic
            >
                <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold text-zinc-50">{{ $week['label'] }}</p>
                    <p class="mt-0.5 text-xs text-zinc-400">
                        {{ $week['start']->format('M j') }} &ndash; {{ $week['end']->format('M j, Y') }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    @if ($timecard)
                        <div class="text-right">
                            <p class="text-lg font-semibold tabular-nums text-zinc-50">
                                {{ number_format((float) $timecard->total_hours, 2) }}<span class="ml-0.5 text-xs font-normal text-zinc-400">{{ __('h') }}</span>
                            </p>
                            @include('timecards::livewire.mobile.timecards.partials.status-badge', ['status' => $timecard->status])
                        </div>
                    @elseif ($href)
                        <span class="inline-flex min-h-9 items-center rounded-full bg-zinc-100 px-3 text-xs font-semibold text-zinc-900">
                            {{ __('Start') }}
                        </span>
                    @else
                        <span class="text-xs text-zinc-500">{{ __('Not started') }}</span>
                    @endif

                    @if ($href)
                        <svg class="h-4 w-4 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
            </a>
        @endforeach
    </section>

    {{-- Other weeks --}}
    <section class="flex flex-col gap-2">
        <p class="px-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Other Weeks') }}</p>

        @if ($timecards->isEmpty())
            <p class="rounded-2xl border border-dashed border-zinc-800 px-4 py-6 text-center text-sm text-zinc-500">
                {{ __('No other timecards yet.') }}
            </p>
        @else
            <div class="divide-y divide-zinc-800 overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
                @foreach ($timecards as $timecard)
                    <a
                        href="{{ route('timecards.mobile.show', $timecard) }}"
                        wire:navigate
                        wire:key="tc-{{ $timecard->id }}"
                        class="flex items-center justify-between gap-3 px-4 py-3.5 active:bg-zinc-800"
                        data-mobile-haptic
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-100">
                                {{ $timecard->week_starting?->format('M j') }} &ndash; {{ $timecard->week_ending?->format('M j, Y') }}
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-500">
                                {{ $timecard->entries_count }} {{ Str::plural('entry', $timecard->entries_count) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-sm font-semibold tabular-nums text-zinc-200">{{ number_format((float) $timecard->total_hours, 2) }}h</span>
                            @include('timecards::livewire.mobile.timecards.partials.status-badge', ['status' => $timecard->status])
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($timecards->hasPages())
            <div class="flex justify-center gap-4 pt-2">
                <button type="button" wire:click="previousPage" @disabled($timecards->onFirstPage()) class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:border-zinc-800 disabled:text-zinc-600" data-mobile-haptic>{{ __('Previous') }}</button>
                <button type="button" wire:click="nextPage" @disabled(! $timecards->hasMorePages()) class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:border-zinc-800 disabled:text-zinc-600" data-mobile-haptic>{{ __('Next') }}</button>
            </div>
        @endif
    </section>
</div>
