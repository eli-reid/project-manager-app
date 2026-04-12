<section class="w-full space-y-6">
    <flux:button icon="arrow-left" :href="route('reports.financial.index')" size="sm">
        {{ __('Financial Reports') }}
    </flux:button>

    <div class="space-y-2">
        <flux:heading size="xl">{{ __('Payroll Labor Cost by Project and Cost Code') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Placeholder page. Labor cost analytics by project, cost code, and employee will be implemented in the next payroll reporting phase.') }}
        </flux:text>
    </div>
</section>
