<section class="w-full space-y-6">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
            {{ __('Financial Reports') }}
        </flux:button>
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Monthly Financial Performance') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Month-over-month revenue, costs, and margin.') }}
            </flux:text>
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <flux:field class="w-36">
            <flux:label>{{ __('Year') }}</flux:label>
            <flux:select wire:model.live="year">
                @foreach ($availableYears as $yr)
                    <option value="{{ $yr }}">{{ $yr }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        @can('reports.financial.export')
            <flux:button wire:click="exportCsv" icon="arrow-down-tray">
                {{ __('Export CSV') }}
            </flux:button>
        @endcan

        @can('reports.financial.export')
            <flux:button
                icon="printer"
                :href="route('reports.financial.monthly-performance.print', ['year' => $year])"
                target="_blank"
            >
                {{ __('Print / PDF') }}
            </flux:button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Month') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Timecard Hours') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Invoice Revenue') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Stock Cost') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Gross Margin') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @foreach ($months as $row)
                    <tr wire:key="month-{{ $row['month'] }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['month_label'] }}</td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($row['hours'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">${{ number_format($row['revenue'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">${{ number_format($row['stock_cost'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold @if ($row['margin'] >= 0) text-green-600 dark:text-green-400 @else @endif">
                            ${{ number_format($row['margin'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                <tr>
                    <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Total') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ number_format(collect($months)->sum('hours'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                        ${{ number_format(collect($months)->sum('revenue'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                        ${{ number_format(collect($months)->sum('stock_cost'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold {{ collect($months)->sum('margin') >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        ${{ number_format(collect($months)->sum('margin'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
