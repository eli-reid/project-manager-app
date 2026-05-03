<section class="w-full space-y-6">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" :href="route('reports.operational.index')" size="sm">
            {{ __('Operational Reports') }}
        </flux:button>
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Leave Balance Summary') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Allotted and used sick and vacation hours for all active employees.') }}
            </flux:text>
        </div>
    </div>

    <div class="flex items-center justify-end">
        <flux:button wire:click="exportCsv" icon="arrow-down-tray">
            {{ __('Export CSV') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300" rowspan="2">
                        {{ __('Employee') }}
                    </th>
                    <th class="border-b border-zinc-200 px-4 py-2 text-center font-medium text-red-600 dark:border-zinc-700 dark:text-red-400" colspan="3">
                        {{ __('Sick Time') }}
                    </th>
                    <th class="border-b border-zinc-200 px-4 py-2 text-center font-medium text-sky-600 dark:border-zinc-700 dark:text-sky-400" colspan="3">
                        {{ __('Vacation Time') }}
                    </th>
                </tr>
                <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Allotted') }}</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Used') }}</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Remaining') }}</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Allotted') }}</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Used') }}</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Remaining') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($rows as $row)
                    <tr wire:key="row-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['name'] }}</td>

                        {{-- Sick columns --}}
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ number_format($row['sick_allowed'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ number_format($row['sick_used'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold @if($row['sick_remaining'] < 0) text-red-600 dark:text-red-400 @else text-zinc-900 dark:text-zinc-100 @endif">
                            {{ number_format($row['sick_remaining'], 2) }}
                        </td>

                        {{-- Vacation columns --}}
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ number_format($row['vacation_allowed'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ number_format($row['vacation_used'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold @if($row['vacation_remaining'] < 0) text-red-600 dark:text-red-400 @else text-zinc-900 dark:text-zinc-100 @endif">
                            {{ number_format($row['vacation_remaining'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No active employees found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
