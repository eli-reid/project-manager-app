<section class="w-full space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">{{ __('Financial Reports') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Phase 1 reporting workspace for profitability and cost analysis.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('Project') }}</flux:label>
                <flux:select wire:model.live="projectId">
                    <option value="">{{ __('Select a project') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_number ? $project->project_number.' - '.$project->name : $project->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="projectId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('From Date') }}</flux:label>
                <input type="date" wire:model.live="fromDate" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('To Date') }}</flux:label>
                <input type="date" wire:model.live="toDate" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            </flux:field>
        </div>

        @can('reports.financial.export')
            <div class="mt-4 flex justify-end">
                <flux:button wire:click="exportProjectReport" icon="arrow-down-tray" :disabled="$projectId === ''">
                    {{ __('Export CSV') }}
                </flux:button>
            </div>
        @endcan
    </div>

    @if ($selectedProject !== null && $projectReport !== null)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Project Report') }}</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $selectedProject->project_number ? $selectedProject->project_number.' - '.$selectedProject->name : $selectedProject->name }}
                </flux:text>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Timecard Hours') }}</flux:text>
                    <flux:heading size="lg">{{ number_format($projectReport['timecard_hours'], 2) }}</flux:heading>
                </article>

                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Daily Reports') }}</flux:text>
                    <flux:heading size="lg">{{ number_format($projectReport['daily_reports_count']) }}</flux:heading>
                </article>

                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Stock Orders') }}</flux:text>
                    <flux:heading size="lg">{{ number_format($projectReport['stock_orders_count']) }}</flux:heading>
                </article>

                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Total Invoice Amount') }}</flux:text>
                    <flux:heading size="lg">${{ number_format($projectReport['total_invoice_amount'], 2) }}</flux:heading>
                </article>

                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Average Invoice Amount') }}</flux:text>
                    <flux:heading size="lg">${{ number_format($projectReport['average_invoice_amount'], 2) }}</flux:heading>
                </article>
            </div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($phaseOneReports as $report)
            <article wire:key="report-card-{{ $report['key'] }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ $report['label'] }}</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $report['description'] }}</flux:text>
                </div>

                <div class="mt-4">
                    <flux:badge color="amber">{{ __('Coming next in this feature wave') }}</flux:badge>
                </div>
            </article>
        @endforeach
    </div>
</section>
