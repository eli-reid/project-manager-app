<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Weekly Employee Hours</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Summary of approved employee hours for payroll submission.
            </p>
        </div>
        <button
            onclick="window.print()"
            class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
        >
            Print
        </button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Week Starting</label>
            <input
                type="date"
                wire:model.live="weekStart"
                class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
            />
        </div>
        <div class="flex gap-2">
            <button
                wire:click="previousWeek"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Previous Week
            </button>
            <button
                wire:click="nextWeek"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Next Week
            </button>
        </div>
    </div>

    <div class="space-y-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30">
        <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
            <strong>Week of {{ \Carbon\CarbonImmutable::parse($weekStart)->format('M j, Y') }} to {{ $this->weekEnd->format('M j, Y') }}</strong>
        </p>
        <p class="text-sm text-blue-800 dark:text-blue-300">
            Showing approved and submitted timecards only. Total employees: <strong>{{ $employeeHours->count() }}</strong>
        </p>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Employee Name
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Employee ID
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Total Hours
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($employeeHours as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item['user']->first_name }} {{ $item['user']->last_name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item['user']->id }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($item['hours'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No approved timecards for this week.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($employeeHours->count() > 0)
                    <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Total Hours:
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($totalHours, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="text-center text-xs text-zinc-500 dark:text-zinc-400 print:hidden">
        <p>Generated on {{ now()->format('M j, Y \a\t g:i A') }}</p>
    </div>
</div>

<style>
    @media print {
        .print\:hidden {
            display: none;
        }

        body {
            background: white;
        }

        * {
            box-shadow: none !important;
        }
    }
</style>
