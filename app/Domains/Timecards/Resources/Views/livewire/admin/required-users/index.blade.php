<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Timecard Required Users</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage which employees are required to submit timecards.</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <input type="text" wire:model.live="searchTerm" placeholder="Search employees by name or email..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Required</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reminders Enabled</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($users as $item)
                        <tr wire:key="user-{{ $item['user']->id }}">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ trim($item['user']->first_name . ' ' . $item['user']->last_name) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $item['user']->email }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    type="button"
                                    wire:click="toggleRequired({{ $item['user']->id }})"
                                    @class([
                                        'inline-flex items-center justify-center rounded-full p-2 transition-colors',
                                        'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300' => $item['is_required'],
                                        'bg-zinc-100 text-zinc-500 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-500' => !$item['is_required'],
                                    ])
                                >
                                    @if ($item['is_required'])
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($item['is_required'])
                                    <input
                                        type="checkbox"
                                        @checked($item['entry']?->reminders_enabled ?? true)
                                        wire:change="updateRemindersEnabled({{ $item['user']->id }}, $event.target.checked)"
                                        class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900"
                                    />
                                @else
                                    <span class="text-xs text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($item['is_required'])
                                    <button
                                        type="button"
                                        class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200"
                                        onclick="alert('Edit effective dates feature coming soon')"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No active employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($users->count() > 0)
        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            Showing {{ $users->count() }} employee(s).
        </p>
    @endif
</div>
