<div class="flex flex-col gap-4 px-4 py-5 pb-28">

    {{-- New Timecard quick-create chips --}}
    @if (count($futureWeeks) > 0)
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Start New Timecard') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($futureWeeks as $week)
                    <a
                        href="{{ route('timecards.mobile.create', ['week_starting' => $week['start']]) }}"
                        class="inline-flex min-h-10 items-center rounded-full border border-zinc-700 bg-zinc-800 px-4 text-xs font-semibold text-zinc-100 active:bg-zinc-700"
                        wire:navigate
                        data-mobile-haptic
                    >
                        {{ $week['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Timecard list --}}
    <div class="flex flex-col gap-3">
        @forelse ($timecards as $timecard)
            @php
                $statusColor = match ($timecard->status) {
                    \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT => 'text-zinc-400 border-zinc-700 bg-zinc-800',
                    \App\Domains\Timecards\Models\Timecard::STATUS_SUBMITTED => 'text-amber-300 border-amber-800/60 bg-amber-950/30',
                    \App\Domains\Timecards\Models\Timecard::STATUS_APPROVED => 'text-emerald-300 border-emerald-800/60 bg-emerald-950/30',
                    \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED => 'text-red-300 border-red-800/60 bg-red-950/30',
                    default => 'text-zinc-400 border-zinc-700 bg-zinc-800',
                };
            @endphp
            <a
                href="{{ route('timecards.mobile.show', $timecard) }}"
                class="flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4 active:bg-zinc-800"
                wire:navigate
                wire:key="tc-{{ $timecard->id }}"
                data-mobile-haptic
            >
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-100">
                        {{ optional($timecard->week_starting)->format('M j') }} &ndash; {{ optional($timecard->week_ending)->format('M j, Y') }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-400">
                        {{ $timecard->entries_count }} {{ Str::plural('entry', $timecard->entries_count) }}
                        &middot;
                        {{ number_format((float) $timecard->total_hours, 2) }} {{ __('hrs') }}
                    </p>
                </div>

                <div class="ml-3 shrink-0 flex items-center gap-3">
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColor }}">
                        {{ str($timecard->status)->replace('-', ' ')->headline() }}
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-10 text-center">
                <svg class="mx-auto h-10 w-10 text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-zinc-400">{{ __('No timecards yet.') }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Use the chips above to start your first timecard.') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($timecards->hasPages())
        <div class="flex justify-center gap-4 pt-2">
            @if ($timecards->onFirstPage())
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Previous') }}</span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Previous') }}</button>
            @endif

            @if ($timecards->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Next') }}</button>
            @else
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Next') }}</span>
            @endif
        </div>
    @endif

</div>
