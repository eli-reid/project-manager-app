<section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('My Timecards') }}</h3>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Recent timecard activity.') }}</p>
        </div>
        <a
            href="{{ route('timecards.index') }}"
            class="shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            wire:navigate
        >
            {{ __('View all') }}
        </a>
    </div>

    @if ($currentTimecard === null)
        <div class="mb-3 flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2.5 dark:bg-amber-900/20">
            <p class="text-xs text-amber-700 dark:text-amber-400">
                {{ __('No timecard for the week of :date.', ['date' => $currentWeekStart->format('M j, Y')]) }}
            </p>
            <a
                href="{{ route('timecards.create', ['week_starting' => $currentWeekStart->toDateString()]) }}"
                class="ml-3 shrink-0 rounded-md bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-500 dark:bg-amber-700 dark:hover:bg-amber-600"
                wire:navigate
            >
                {{ __('Create') }}
            </a>
        </div>
    @endif

    @forelse ($timecards as $timecard)
        <a
            href="{{ route('timecards.show', $timecard) }}"
            class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
            wire:navigate
        >
            <div class="min-w-0">
                <p class="truncate text-sm text-zinc-900 dark:text-zinc-100">
                    {{ $timecard->week_starting?->format('M j') }} &ndash; {{ $timecard->week_ending?->format('M j, Y') }}
                </p>
            </div>
            <div class="ml-3 flex shrink-0 items-center gap-3">
                <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">
                    {{ number_format((float) $timecard->total_hours, 1) }}h
                </span>
                @php
                    $statusColors = [
                        'draft'     => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
                        'submitted' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
                        'approved'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
                        'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300',
                    ];
                @endphp
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$timecard->status] ?? 'bg-zinc-100 text-zinc-700' }}">
                    {{ str($timecard->status)->replace('-', ' ')->headline() }}
                </span>
            </div>
        </a>
    @empty
        <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No recent timecards.') }}</p>
    @endforelse
</section>
