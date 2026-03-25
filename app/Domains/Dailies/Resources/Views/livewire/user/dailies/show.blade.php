<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Daily Report Details') }}</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Phase 1 scaffold complete. Expanded user details are planned for Phase 3.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Status') }}: {{ str($dailyReport->status)->headline() }}</p>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Date') }}: {{ optional($dailyReport->report_date)->format('M j, Y') }}</p>
    </div>
</div>
