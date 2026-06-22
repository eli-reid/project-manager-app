<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-1">
        <flux:heading size="xl">{{ __('Payroll Audit Trail') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Immutable payroll mutation history with digest chain validation status.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="w-40">
                <flux:label>{{ __('From') }}</flux:label>
                <flux:input type="date" wire:model.live="fromDate" />
            </flux:field>

            <flux:field class="w-40">
                <flux:label>{{ __('To') }}</flux:label>
                <flux:input type="date" wire:model.live="toDate" />
            </flux:field>

            <flux:field class="w-56">
                <flux:label>{{ __('Action Contains') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="actionContains" placeholder="pay-runs" />
            </flux:field>

            <flux:field class="w-48">
                <flux:label>{{ __('Filter') }}</flux:label>
                <flux:select wire:model.live="invalidDigestsOnly">
                    <option value="0">{{ __('All Logs') }}</option>
                    <option value="1">{{ __('Invalid Digests Only') }}</option>
                </flux:select>
            </flux:field>

            @can('reports.payroll.export')
                <flux:button wire:click="exportCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Visible Audit Logs') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ number_format($summary['total']) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Invalid Digest Entries') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ number_format($summary['invalid']) }}</flux:heading>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('When') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Action') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actor') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Target') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Digest') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($rows as $row)
                    <tr wire:key="audit-row-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-3 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['created_at'] }}</td>
                        <td class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['action'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['actor'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['target'] }}</td>
                        <td class="px-3 py-3">
                            @if ($row['digest_valid'])
                                <flux:badge color="green">{{ __('Valid') }}</flux:badge>
                            @else
                                <flux:badge color="red">{{ __('Invalid') }}</flux:badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No payroll audit logs found for the selected filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
