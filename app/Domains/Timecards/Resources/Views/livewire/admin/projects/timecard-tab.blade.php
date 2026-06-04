<div class="space-y-4">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Hours</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalHours, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Regular</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($regularHours, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Overtime</p>
            <p class="mt-2 text-2xl font-bold {{ $overtimeHours > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($overtimeHours, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Double Time</p>
            <p class="mt-2 text-2xl font-bold {{ $doubleTimeHours > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($doubleTimeHours, 2) }}</p>
        </div>
    </div>

    @if ($hoursByUser->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Hours by Employee</p>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($hoursByUser as $row)
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->user?->name ?? 'Unknown' }}</span>
                        <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">{{ number_format((float) $row->total_hours, 2) }}h</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recent Time Entries</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hours</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Cost Code</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($recentTimeEntries as $entry)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $entry->date?->format('M j, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $entry->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format((float) $entry->hours, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($entry->costCode)
                                    <span class="font-mono">{{ $entry->costCode->code }}</span>
                                    <span class="ml-1 text-xs">{{ $entry->costCode->name }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $entry->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No time entries for this project.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
