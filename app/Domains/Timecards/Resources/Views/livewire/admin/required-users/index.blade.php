<section class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Timecard Required Users</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage which employees are required to submit timecards.</p>
        </div>

        <a href="{{ route('admin.timecards.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>
            Back to Timecards
        </a>
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
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Effective Date Range</th>
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
                                    wire:click="toggleRequired('{{ $item['user']->id }}')"
                                    role="switch"
                                    aria-checked="{{ $item['is_required'] ? 'true' : 'false' }}"
                                    @class([
                                        'relative inline-flex h-6 w-11 items-center rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
                                        'border-emerald-500 bg-emerald-500/90 hover:bg-emerald-500 dark:border-emerald-400 dark:bg-emerald-500' => $item['is_required'],
                                        'border-zinc-300 bg-zinc-200 hover:bg-zinc-300 dark:border-zinc-700 dark:bg-zinc-700 dark:hover:bg-zinc-600' => !$item['is_required'],
                                    ])
                                >
                                    <span
                                        @class([
                                            'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition-transform',
                                            'translate-x-5' => $item['is_required'],
                                            'translate-x-0' => !$item['is_required'],
                                        ])
                                    ></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($item['is_required'])
                                    <input
                                        type="checkbox"
                                        @checked($item['entry']?->reminders_enabled ?? true)
                                        wire:change="updateRemindersEnabled('{{ $item['user']->id }}', $event.target.checked)"
                                        class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900"
                                    />
                                @else
                                    <span class="text-xs text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($item['is_required'])
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div class="text-left">
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Start</label>
                                            <input
                                                type="date"
                                                value="{{ $item['entry']?->effective_start_date?->format('Y-m-d') }}"
                                                wire:change="setEffectiveDates('{{ $item['user']->id }}', $event.target.value || null, '{{ $item['entry']?->effective_end_date?->format('Y-m-d') }}')"
                                                class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                        </div>
                                        <div class="text-left">
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">End</label>
                                            <input
                                                type="date"
                                                value="{{ $item['entry']?->effective_end_date?->format('Y-m-d') }}"
                                                wire:change="setEffectiveDates('{{ $item['user']->id }}', '{{ $item['entry']?->effective_start_date?->format('Y-m-d') }}', $event.target.value || null)"
                                                class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                        </div>
                                    </div>
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

    @error('effectiveDates')
        <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror
</section>
