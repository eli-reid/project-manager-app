<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Email Accounts</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Search, filter, and manage cPanel mailbox status from cached account data.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="triggerSync" icon="arrow-path">Sync Now</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.create')" icon="plus" wire:navigate>Create Mailbox</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.dashboard')" icon="home" wire:navigate>Dashboard</flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-2">
            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input wire:model.live="search" placeholder="Search by email" />
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="status">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="high-usage">High Usage</option>
                </flux:select>
            </flux:field>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:field class="min-w-56">
                <flux:label>Bulk Password</flux:label>
                <flux:input type="text" wire:model="bulkPassword" placeholder="Minimum 12 characters" />
                <flux:error name="bulkPassword" />
            </flux:field>

            <flux:button size="sm" variant="ghost" wire:click="bulkSuspend" wire:confirm="Suspend selected mailboxes?">Bulk Suspend</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="bulkUnsuspend" wire:confirm="Unsuspend selected mailboxes?">Bulk Unsuspend</flux:button>
            <flux:button size="sm" variant="primary" wire:click="bulkResetPassword" wire:confirm="Reset password for selected mailboxes?">Bulk Password Reset</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear Selection</flux:button>

            <flux:text class="ml-auto text-xs text-zinc-500 dark:text-zinc-400">
                {{ count($selectedAccountIds) }} selected
            </flux:text>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Select</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Quota</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Usage</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Last Synced</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($accounts as $account)
                        <tr wire:key="cached-email-{{ $account->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-xs">
                                <flux:checkbox
                                    :checked="in_array($account->id, $selectedAccountIds, true)"
                                    wire:click="toggleAccountSelection('{{ $account->id }}')"
                                />
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $account->email }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ trim(($account->user?->first_name ?? '').' '.($account->user?->last_name ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $account->quota }} MB</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $account->usage }} MB ({{ number_format((float) $account->usage_percentage, 2) }}%)</td>
                            <td class="px-4 py-3 text-xs">
                                @if ($account->suspended)
                                    <span class="rounded-md bg-amber-100 px-2 py-1 font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Suspended</span>
                                @else
                                    <span class="rounded-md bg-emerald-100 px-2 py-1 font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $account->last_synced_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="xs" variant="ghost" :href="route('admin.cpanel.manage.email-accounts.show', $account)" wire:navigate>Details</flux:button>
                                @if ($account->suspended)
                                    <flux:button size="xs" variant="ghost" wire:click="unsuspend('{{ $account->id }}')">Unsuspend</flux:button>
                                @else
                                    <flux:button size="xs" variant="ghost" wire:click="suspend('{{ $account->id }}')">Suspend</flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No cached accounts found. Run sync to load accounts.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $accounts->links() }}
        </div>
    </div>
</div>
