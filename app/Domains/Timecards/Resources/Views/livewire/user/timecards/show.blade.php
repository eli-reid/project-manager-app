<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Timecard Details') }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ optional($timecard->week_starting)->format('M j, Y') }} - {{ optional($timecard->week_ending)->format('M j, Y') }}
            </flux:text>
        </div>

        <div class="flex gap-2">
            @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT)
                <a href="{{ route('timecards.edit', $timecard) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>{{ __('Edit') }}</a>
                <button type="button" wire:click="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">{{ __('Submit') }}</button>
            @endif

            @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED)
                <button type="button" wire:click="resetToDraft" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Reset To Draft') }}</button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ str($timecard->status)->replace('-', ' ')->headline() }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $timecard->total_hours, 2) }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Entries') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $timecard->entries->count() }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Sick Remaining') }}</p>
            <p class="mt-2 text-xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format((float) data_get($leaveBalances, 'sick.remaining', 0), 2) }} {{ __('hrs') }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">{{ __('Used: :used / :allowed', ['used' => number_format((float) data_get($leaveBalances, 'sick.used', 0), 2), 'allowed' => number_format((float) data_get($leaveBalances, 'sick.allowed', 0), 2)]) }}</p>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">{{ __('Vacation Remaining') }}</p>
            <p class="mt-2 text-xl font-semibold text-sky-900 dark:text-sky-100">{{ number_format((float) data_get($leaveBalances, 'vacation.remaining', 0), 2) }} {{ __('hrs') }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">{{ __('Used: :used / :allowed', ['used' => number_format((float) data_get($leaveBalances, 'vacation.used', 0), 2), 'allowed' => number_format((float) data_get($leaveBalances, 'vacation.allowed', 0), 2)]) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</p>
        <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $timecard->notes ?: __('No notes added.') }}</p>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Entries') }}</p>
            @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT)
                <a href="{{ route('timecards.edit', $timecard) }}" class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white" wire:navigate>{{ __('Manage Entries') }}</a>
            @endif
        </div>

        @if ($timecard->entries->isEmpty())
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No entries added yet.') }}</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($timecard->entries as $entry)
                            <tr wire:key="timecard-entry-{{ $entry->id }}">
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ optional($entry->date)->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->project?->name ?? $entry->custom_project_name ?? __('Unassigned') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->hours, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>