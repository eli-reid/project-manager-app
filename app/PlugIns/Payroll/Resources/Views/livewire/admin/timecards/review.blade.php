<section class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Payroll Timecard Review</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Validate payroll-critical timecard entries and compare them against daily report totals.</p>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end mb-2">
        <div class="flex-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Week Starting</label>
            <input
                type="date"
                wire:model.live="weekStarting"
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
    <p class="-mt-2 text-xs text-zinc-500 dark:text-zinc-400">Week ending {{ $weekEnding }}</p>

    <div class="grid gap-3 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</label>
            <select wire:model.live="userFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All Employees</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ trim($user->first_name.' '.$user->last_name) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</label>
            <select wire:model.live="projectFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All Projects</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Visibility</label>
            <select wire:model.live="issuesOnly" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="0">All Entries</option>
                <option value="1">Issues Only</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Cost Code</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hours</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Timecard Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Validation</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reconciliation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($entries as $entry)
                        @php
                            $validation = $validationByEntryId->get((string) $entry->id);
                            $date = optional($entry->date)->toDateString();
                            $projectKey = (string) ($entry->project_id ?? '');
                            $isMismatch = $reconciliationMismatchKeys->contains($date.'|'.$projectKey)
                                || $reconciliationMismatchKeys->contains($date.'|');
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ optional($entry->date)->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ trim((string) ($entry->user?->first_name ?? '').' '.(string) ($entry->user?->last_name ?? '')) }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->project?->name ?? ($entry->custom_project_name ?: 'Unassigned') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->costCode?->code ? $entry->costCode->code.' - '.$entry->costCode->description : '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) $entry->hours, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ ucfirst((string) ($entry->timecard?->status ?? 'unknown')) }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($validation && $validation->hasBlocks())
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">Blocking</span>
                                    <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">{{ collect($validation->blocks())->first()?->message }}</p>
                                @elseif ($validation && $validation->hasWarnings())
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Warning</span>
                                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ collect($validation->warnings())->first()?->message }}</p>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Pass</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $isMismatch ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                    {{ $isMismatch ? 'Mismatch' : 'Aligned' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No timecard entries found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
