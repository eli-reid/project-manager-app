<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Daily Reports') }}</p>
                <p class="mt-1 text-sm text-zinc-300">{{ __('Track drafts and submission status.') }}</p>
            </div>

            @can('create', \App\Domains\Dailies\Models\DailyReport::class)
                <a
                    href="{{ route('dailies.mobile.create') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-800 px-3 text-xs font-semibold text-zinc-100 active:bg-zinc-700"
                    wire:navigate
                    data-mobile-haptic
                >
                    {{ __('New Daily') }}
                </a>
            @endcan
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <select wire:model.live="status" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach ($statuses as $statusValue)
                    <option value="{{ $statusValue }}">{{ str($statusValue)->headline() }}</option>
                @endforeach
            </select>

            <input type="date" wire:model.live="from_date" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none" />

            <input type="date" wire:model.live="to_date" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none" />
        </div>

        <button
            type="button"
            wire:click="$set('status', ''); $set('from_date', null); $set('to_date', null)"
            class="mt-2 inline-flex min-h-10 items-center rounded-xl border border-zinc-700 px-3 text-xs font-semibold text-zinc-300 active:bg-zinc-800"
            data-mobile-haptic
        >
            {{ __('Reset Filters') }}
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($reports as $report)
            @php
                $statusColor = match ($report->status) {
                    \App\Domains\Dailies\Models\DailyReport::STATUS_DRAFT => 'text-zinc-400 border-zinc-700 bg-zinc-800',
                    \App\Domains\Dailies\Models\DailyReport::STATUS_SUBMITTED => 'text-amber-300 border-amber-800/60 bg-amber-950/30',
                    \App\Domains\Dailies\Models\DailyReport::STATUS_APPROVED => 'text-emerald-300 border-emerald-800/60 bg-emerald-950/30',
                    \App\Domains\Dailies\Models\DailyReport::STATUS_REJECTED => 'text-red-300 border-red-800/60 bg-red-950/30',
                    default => 'text-zinc-400 border-zinc-700 bg-zinc-800',
                };
            @endphp

            <a
                href="{{ route('dailies.mobile.show', $report) }}"
                class="flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4 active:bg-zinc-800"
                wire:key="mobile-daily-{{ $report->id }}"
                wire:navigate
                data-mobile-haptic
            >
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-100">{{ optional($report->report_date)->format('M j, Y') }}</p>
                    <p class="mt-0.5 truncate text-xs text-zinc-400">{{ $report->project?->name ?? ($report->custom_project_name ?: '—') }}</p>
                    <p class="mt-0.5 text-xs text-zinc-500">{{ number_format((float) $report->total_hours, 2) }} {{ __('hrs') }}</p>
                </div>

                <div class="ml-3 shrink-0 flex items-center gap-3">
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColor }}">
                        {{ str($report->status)->headline() }}
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-10 text-center">
                <p class="text-sm font-medium text-zinc-400">{{ __('No daily reports found.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($reports->hasPages())
        <div class="flex justify-center gap-4 pt-2">
            @if ($reports->onFirstPage())
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Previous') }}</span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Previous') }}</button>
            @endif

            @if ($reports->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Next') }}</button>
            @else
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Next') }}</span>
            @endif
        </div>
    @endif
</div>
