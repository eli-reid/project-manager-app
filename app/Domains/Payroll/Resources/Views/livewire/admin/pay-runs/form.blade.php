<div class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Create Preview Pay Run</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create a preview run for submitted and approved timecards in the selected pay period.</p>
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
            <div class="space-y-1">
                <label for="pay_period_start" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pay Period Start</label>
                <input id="pay_period_start" type="date" wire:model="pay_period_start" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            </div>

            <div class="space-y-1">
                <label for="pay_period_end" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pay Period End</label>
                <input id="pay_period_end" type="date" wire:model="pay_period_end" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            </div>
        </div>

        <div class="space-y-1 sm:max-w-xs">
            <label for="pay_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pay Date</label>
            <input id="pay_date" type="date" wire:model="pay_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Preview Run</span>
                <span wire:loading>Creating...</span>
            </button>

            <a href="{{ route('admin.payroll.runs.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Cancel
            </a>
        </div>
    </form>
</div>
