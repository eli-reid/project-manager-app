<section class="w-full max-w-3xl space-y-6">
    <div class="space-y-1">
        <flux:heading size="xl">Create Preview Pay Run</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">Create a preview run for submitted and approved timecards in the selected pay period.</flux:text>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="createPreview" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field>
                <flux:label for="pay_period_start">Pay Period Start</flux:label>
                <flux:input id="pay_period_start" type="date" wire:model="pay_period_start" />
                <flux:error name="pay_period_start" />
            </flux:field>

            <flux:field>
                <flux:label for="pay_period_end">Pay Period End</flux:label>
                <flux:input id="pay_period_end" type="date" wire:model="pay_period_end" />
                <flux:error name="pay_period_end" />
            </flux:field>
        </div>

        <div class="sm:max-w-xs">
            <flux:field>
                <flux:label for="pay_date">Pay Date</flux:label>
                <flux:input id="pay_date" type="date" wire:model="pay_date" />
                <flux:error name="pay_date" />
            </flux:field>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Preview Run</span>
                <span wire:loading>Creating...</span>
            </flux:button>

            <flux:button :href="route('admin.payroll.runs.index')" wire:navigate>
                Cancel
            </flux:button>
        </div>
    </form>
</section>
