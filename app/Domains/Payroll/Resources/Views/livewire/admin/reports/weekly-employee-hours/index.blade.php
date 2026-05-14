<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Weekly Employee Hours</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Summary of approved employee hours for payroll submission.
            </p>
        </div>
        <div class="flex gap-2">
            <button
                onclick="window.print()"
                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
            >
                Print
            </button>
            <button
                wire:click="openEmailModal"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:text-white"
            >
                Email as PDF
            </button>
        </div>
        {{-- Email Modal --}}
        <x-ui.email-report-modal wire:model="showEmailModal" :title="'Send Report as Email'" />
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
            Showing approved and submitted timecards only. Total employees: <strong>{{ $this->employeeHours->count() }}</strong>
        </p>
        @if ($this->canAdjustHours)
            <p class="text-sm text-blue-800 dark:text-blue-300">
                Admin overrides are stored separately from timecards and tracked in the adjustment history below.
            </p>
        @endif
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
                            Source Hours
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Reported Hours
                        </th>
                        @if ($this->canAdjustHours)
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Actions
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->employeeHours as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item['first_name'] }} {{ $item['last_name'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item['user_id'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">
                                {{ number_format($item['source_hours'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($item['hours'], 2) }}
                                @if ($item['is_adjusted'])
                                    <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Adjusted
                                    </span>
                                @endif
                            </td>
                            @if ($this->canAdjustHours)
                                <td class="px-4 py-3 text-right text-sm">
                                    <button
                                        wire:click="startEditing('{{ $item['user_id'] }}')"
                                        class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Edit
                                    </button>
                                </td>
                            @endif
                        </tr>

                        @if ($this->canAdjustHours && $editingUserId === $item['user_id'])
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <td colspan="{{ $this->canAdjustHours ? 5 : 4 }}" class="px-4 py-4">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                                Adjusted Hours
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                wire:model="editHours.{{ $item['user_id'] }}"
                                                class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                            @error('editHours.'.$item['user_id'])
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                                Reason
                                            </label>
                                            <input
                                                type="text"
                                                wire:model="editReasons.{{ $item['user_id'] }}"
                                                class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                            @error('editReasons.'.$item['user_id'])
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-3 flex justify-end gap-2">
                                        <button
                                            wire:click="cancelEditing"
                                            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            wire:click="saveAdjustment('{{ $item['user_id'] }}')"
                                            class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                                        >
                                            Save Adjustment
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $this->canAdjustHours ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No approved timecards for this week.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->employeeHours->count() > 0)
                    <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Total Hours:
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($this->totalHours, 2) }}
                            </td>
                            @if ($this->canAdjustHours)
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-4 px-4 py-4">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Weekly Hour Adjustment Report</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    View a dedicated report of weekly-hour overrides tracked separately from timecards.
                </p>
            </div>
            <a
                href="{{ route('admin.payroll.reports.weekly-hour-adjustments', ['week_start' => $weekStart]) }}"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                wire:navigate
            >
                Open Report
            </a>
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
