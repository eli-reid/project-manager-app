<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-1">
        <flux:heading size="xl">{{ __('Payroll Labor Cost by Project and Cost Code') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Estimated labor cost grouped by project, cost code, and employee from approved timecards.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="w-56">
                <flux:label>{{ __('Project') }}</flux:label>
                <flux:select wire:model.live="projectId">
                    <option value="">{{ __('All Projects') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">
                            {{ $project->project_number ? $project->project_number.' - '.$project->name : $project->name }}
                        </option>
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
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Project') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Cost Code') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Employee') }}</th>
                    <th class="px-3 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Hours') }}</th>
                    <th class="px-3 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Estimated Labor Cost') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($rows as $row)
                    <tr wire:key="labor-row-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['project'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['cost_code'] }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['employee'] }}</td>
                        <td class="px-3 py-3 text-right text-zinc-700 dark:text-zinc-200">{{ number_format($row['total_hours'], 2) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format($row['estimated_labor_cost'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No labor cost rows found for the selected date range and filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($rows) > 0)
                <tfoot class="border-t border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                    <tr>
                        <td colspan="3" class="px-3 py-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Totals') }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($totals['hours'], 2) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">${{ number_format($totals['cost'], 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</section>
