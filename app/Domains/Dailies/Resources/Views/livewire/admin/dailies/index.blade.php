<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Dailies') }}</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Admin daily report scaffold is ready. Workflow screens will be added in the next phase.') }}
        </flux:text>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Worker') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($reports as $report)
                        <tr wire:key="daily-report-{{ $report->id }}">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ optional($report->report_date)->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $report->user?->first_name }} {{ $report->user?->last_name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ str($report->status)->headline() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No daily reports found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $reports->links() }}
        </div>
    </div>
</div>
