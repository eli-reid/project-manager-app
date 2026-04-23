<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Timecard Review') }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ trim(($timecard->user?->first_name ?? '').' '.($timecard->user?->last_name ?? '')) }}
                · {{ optional($timecard->week_starting)->format('M j, Y') }} - {{ optional($timecard->week_ending)->format('M j, Y') }}
            </flux:text>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.timecards.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>Back</a>
            @can('update', $timecard)
                <a href="{{ route('admin.timecards.edit', $timecard) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>Edit</a>
            @endcan
            @can('approve', $timecard)
                <button type="button" wire:click="approve" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
            @endcan
            @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_REJECTED)
                <button type="button" wire:click="resetStatus" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Reset To Draft</button>
            @endif
            @can('delete', $timecard)
                <button type="button" wire:click="delete" wire:confirm="Are you sure you want to delete this timecard?" class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20">Delete</button>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ str($timecard->status)->replace('-', ' ')->headline() }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hours</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $timecard->total_hours, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $timecard->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reviewer</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $timecard->approver?->first_name ?? $timecard->rejector?->first_name ?? '—' }}</p>
        </div>
    </div>

    @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_SUBMITTED)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rejection Reason</label>
            <textarea wire:model="rejectionReason" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
            @error('rejectionReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="reject" class="rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">Reject</button>
            </div>
        </div>
    @elseif ($timecard->rejection_reason)
        <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 shadow-sm dark:border-rose-700 dark:bg-rose-900/20">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-300">Rejection Reason</p>
            <p class="mt-2 text-sm text-rose-700 dark:text-rose-200">{{ $timecard->rejection_reason }}</p>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</p>
        <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $timecard->notes ?: 'No notes added.' }}</p>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Entries</p>

        @if ($timecard->entries->isEmpty())
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No entries found.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($timecard->entries as $entry)
                            <tr wire:key="admin-timecard-entry-{{ $entry->id }}">
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ optional($entry->date)->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->project?->name ?? $entry->custom_project_name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->notes ?: '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->hours, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>