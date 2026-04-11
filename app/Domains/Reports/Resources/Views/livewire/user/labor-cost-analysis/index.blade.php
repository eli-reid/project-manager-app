<section class="w-full space-y-6">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
            {{ __('Financial Reports') }}
        </flux:button>
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Labor Cost Analysis') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Analyze labor hours and estimated cost by project, week, or employee.') }}
            </flux:text>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="w-48">
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

            <flux:field class="w-36">
                <flux:label>{{ __('From') }}</flux:label>
                <flux:input type="date" wire:model.live="fromDate" />
            </flux:field>

            <flux:field class="w-36">
                <flux:label>{{ __('To') }}</flux:label>
                <flux:input type="date" wire:model.live="toDate" />
            </flux:field>

            <flux:field class="w-40">
                <flux:label>{{ __('Group By') }}</flux:label>
                <flux:select wire:model.live="drillDown">
                    <option value="project">{{ __('Project') }}</option>
                    <option value="week">{{ __('Week') }}</option>
                    <option value="employee">{{ __('Employee') }}</option>
                </flux:select>
            </flux:field>

            @can('reports.financial.export')
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
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">
                        {{ match($drillDown) { 'week' => __('Week'), 'employee' => __('Employee'), default => __('Project') } }}
                    </th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Hours') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Est. Labor Cost') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($rows as $row)
                    <tr wire:key="row-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($row['hours'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                            ${{ number_format($row['cost'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No labor data found for the selected filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($rows) > 0)
                <tfoot class="border-t border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                    <tr>
                        <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Total') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ number_format(collect($rows)->sum('hours'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                            ${{ number_format(collect($rows)->sum('cost'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if (collect($rows)->sum('cost') === 0.0 && collect($rows)->sum('hours') > 0)
        <flux:callout icon="information-circle" color="amber">
            {{ __('Hours are recorded but no active pay rates were found. Set up pay rates to see estimated costs.') }}
        </flux:callout>
    @endif
</section>
