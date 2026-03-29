<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Email Management</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Monitor mailbox sync health and jump into account operations.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="triggerSync" icon="arrow-path">Sync Now</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.create')" icon="plus" wire:navigate>Create Mailbox</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.index')" icon="inbox-stack" wire:navigate>Manage Accounts</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.domain-forwarders')" icon="globe-alt" wire:navigate>Domain Forwarders</flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Accounts</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $totalAccounts }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Suspended</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $suspendedAccounts }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">High Usage (>=80%)</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $highUsageAccounts }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Sync Failures</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $syncFailures }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-600 dark:text-zinc-300">
            Last cache sync:
            <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $lastSyncedAt ? \Illuminate\Support\Carbon::parse($lastSyncedAt)->format('M j, Y g:i A') : 'Never' }}
            </span>
        </p>
    </div>
</div>
