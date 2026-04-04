<section class="w-full space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">{{ __('Financial Reports') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Phase 1 reporting workspace for profitability and cost analysis.') }}
        </flux:text>
    </div>

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
