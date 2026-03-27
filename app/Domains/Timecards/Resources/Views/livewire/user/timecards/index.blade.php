<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('My Timecards') }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Track weekly timecards and start new draft weeks.') }}
            </flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($futureWeeks as $futureWeek)
                <a
                    href="{{ route('timecards.create', ['week_starting' => $futureWeek['start']]) }}"
                    class="inline-flex items-center rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    wire:navigate
                >
                    {{ __('New for :range', ['range' => $futureWeek['label']]) }}
                </a>
            @endforeach
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Week') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($timecards as $timecard)
                        <tr wire:key="timecard-{{ $timecard->id }}"
                        wire:navigate
                        href="{{ route('timecards.show', $timecard) }}"
                        class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                        >
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ optional($timecard->week_starting)->format('M j, Y') }} - {{ optional($timecard->week_ending)->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ str($timecard->status)->replace('-', ' ')->headline() }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ number_format((float) $timecard->total_hours, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <x-ui.row-actions-dropdown label="Timecard actions" width="w-36" :menu-height="130">
                                    <a
                                        href="{{ route('timecards.show', $timecard) }}"
                                        class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        wire:navigate
                                        @click="closeMenu()"
                                    >
                                        {{ __('View') }}
                                    </a>

                                    @if ($timecard->status === \App\Domains\Timecards\Models\Timecard::STATUS_DRAFT)
                                        <a
                                            href="{{ route('timecards.edit', $timecard) }}"
                                            class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            wire:navigate
                                            @click="closeMenu()"
                                        >
                                            {{ __('Edit') }}
                                        </a>
                                    @endif
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No timecards found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $timecards->links() }}
        </div>
    </div>
</div>