<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Scheduler System Timing</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Review the active scheduler timing context and tune duplicate-dispatch protection in one place.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.scheduler.tasks.index') }}" class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Back to Tasks
            </a>
            <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                Open All Settings
            </a>
        </div>
    </div>

    @if ($successMessage)
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ $successMessage }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Application Timezone</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $timing['app_timezone'] }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Used when computing task-local schedules.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Current App Time</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $timing['app_now'] }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">What admins expect when reviewing scheduler timing.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Current UTC Time</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $timing['utc_now'] }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Reference used for due-task comparisons and database timestamps.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Storage Baseline</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $timing['storage_timezone'] }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">`next_run_at` is stored and compared in UTC.</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">Dispatch Claim Window</flux:heading>
                    <flux:text class="mt-1">Controls how long a due task is temporarily claimed before another scheduler run can queue it again.</flux:text>
                </div>
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $claimWindowSeconds }} seconds
                </span>
            </div>

            <form wire:submit="save" class="mt-5 space-y-4">
                <flux:field>
                    <flux:label>Claim Window (seconds)</flux:label>
                    <flux:input type="number" min="1" step="1" wire:model.live="claimWindowSeconds" :disabled="! $canEdit" />
                    <flux:text>Use a larger window on shared hosting where cron execution or queue pickup can drift.</flux:text>
                    <flux:error name="claimWindowSeconds" />
                </flux:field>

                @if ($canEdit)
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Save Timing Settings</span>
                            <span wire:loading>Saving...</span>
                        </flux:button>
                        <flux:button type="button" variant="ghost" wire:click="$refresh">
                            Refresh Snapshot
                        </flux:button>
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                        You can review scheduler timing here, but editing the claim window requires settings edit permission.
                    </div>
                @endif
            </form>
        </section>

        <aside class="space-y-4">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Tuning Guide</flux:heading>
                <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">300</span> seconds works well when cron and workers run predictably.</p>
                    <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">900 to 1800</span> seconds is safer for shared hosting or delayed queue processing.</p>
                    <p>Set the window longer than the worst combined delay from cron start, queue pickup, and task execution.</p>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                <flux:heading size="lg">Reminder</flux:heading>
                <flux:text class="mt-3 text-sm">This window protects dispatch. It does not change each task’s actual schedule. After a task runs, the next real `next_run_at` is recalculated from its recurrence rules.</flux:text>
            </section>
        </aside>
    </div>
</div>