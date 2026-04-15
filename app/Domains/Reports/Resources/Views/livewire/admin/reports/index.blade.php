<section class="w-full space-y-6">
    <div class="space-y-1">
        <flux:heading size="xl">Reports</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            Browse reports registered by each domain.
        </flux:text>
    </div>

    @if ($financialReports !== [])
        <div class="space-y-3">
            <flux:heading size="lg">Financial Reports</flux:heading>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($financialReports as $report)
                    <a href="{{ route($report['route']) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500">
                        <div class="space-y-2">
                            <flux:heading size="lg" class="group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ __($report['title']) }}</flux:heading>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ __($report['description']) }}</flux:text>
                        </div>

                        <div class="mt-4">
                            <flux:badge :color="$report['badge_color']">{{ __($report['badge_label']) }}</flux:badge>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($operationalReports !== [])
        <div class="space-y-3">
            <flux:heading size="lg">Operational Reports</flux:heading>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($operationalReports as $report)
                    <a href="{{ route($report['route']) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500">
                        <div class="space-y-2">
                            <flux:heading size="lg" class="group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ __($report['title']) }}</flux:heading>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ __($report['description']) }}</flux:text>
                        </div>

                        <div class="mt-4">
                            <flux:badge :color="$report['badge_color']">{{ __($report['badge_label']) }}</flux:badge>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($financialReports === [] && $operationalReports === [])
        <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
            No reports are available for your current permissions.
        </div>
    @endif
</section>
