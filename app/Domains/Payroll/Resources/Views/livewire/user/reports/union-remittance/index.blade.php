<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-2">
        <flux:heading size="xl">{{ __('Union Remittance') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Placeholder page. Union remittance report outputs and export-ready files will be implemented in the next payroll reporting phase.') }}
        </flux:text>
    </div>
</section>
