<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-1">
        <flux:heading size="xl">{{ __('Union Remittance') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Union remittance totals based on active union deductions and payroll statements.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="w-48">
                <flux:label>{{ __('Union Code') }}</flux:label>
                <flux:select wire:model.live="unionCode">
                    <option value="">{{ __('All Unions') }}</option>
                    @foreach ($unionCodes as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="w-40">
                <flux:label>{{ __('From') }}</flux:label>
                <flux:input type="date" wire:model.live="fromDate" />
            </flux:field>

            <flux:field class="w-40">
                <flux:label>{{ __('To') }}</flux:label>
                <flux:input type="date" wire:model.live="toDate" />
            </flux:field>

            @can('reports.payroll.export')
                <flux:button wire:click="exportCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Pay Run End') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Employee') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Union Code') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Deduction') }}</th>
                    <th class="px-3 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Gross Pay') }}</th>
                    <th class="px-3 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Remittance Due') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($rows as $row)
                    <tr wire:key="remit-row-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-3 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['pay_run'] }}</td>
                        <td class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['employee'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['union_code'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['deduction'] }}</td>
                        <td class="px-3 py-3 text-right text-zinc-700 dark:text-zinc-200">${{ number_format($row['gross_pay'], 2) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format($row['remittance_due'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No union remittance rows found for the selected filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($rows) > 0)
                <tfoot class="border-t border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                    <tr>
                        <td colspan="4" class="px-3 py-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Totals') }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">${{ number_format($totals['gross_pay'], 2) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">${{ number_format($totals['remittance_due'], 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</section>
