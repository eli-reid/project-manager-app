<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Mailbox Details</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Manage password and forwarders for {{ $cachedEmailAccount->email }}.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="launchWebmail" icon="arrow-top-right-on-square">Open Webmail</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.index')" icon="inbox-stack" wire:navigate>Back to Accounts</flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Account</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">Email</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cachedEmailAccount->email }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">Status</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cachedEmailAccount->suspended ? 'Suspended' : 'Active' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">Quota</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cachedEmailAccount->quota }} MB</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-zinc-500 dark:text-zinc-400">Usage</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cachedEmailAccount->usage }} MB</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reset Password</h2>
            <div class="mt-3 space-y-3">
                <flux:field>
                    <div class="flex items-center justify-between gap-2">
                        <flux:label>New Password</flux:label>
                        <flux:button size="xs" variant="ghost" type="button" wire:click="generatePassword">Generate</flux:button>
                    </div>
                    <flux:input type="text" wire:model="newPassword" />
                    <flux:text size="sm">Minimum 12 characters.</flux:text>
                    <flux:error name="newPassword" />
                </flux:field>

                <flux:button variant="primary" wire:click="resetMailboxPassword" icon="key">Update Mailbox Password</flux:button>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Forwarders</h2>

        @if ($forwardersError)
            <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                {{ $forwardersError }}
            </div>
        @endif

        <div class="mt-3 grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
            <flux:field>
                <flux:label>Forward To</flux:label>
                <flux:input wire:model="forwardTo" placeholder="name@example.com" />
                <flux:error name="forwardTo" />
            </flux:field>

            <flux:button variant="primary" wire:click="addForwarder" icon="plus">Add Forwarder</flux:button>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Mailbox</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Forward To</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($forwarders as $forwarder)
                        <tr wire:key="forwarder-{{ md5(($forwarder['email'] ?? '').($forwarder['forward_to'] ?? '')) }}">
                            <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $forwarder['email'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $forwarder['forward_to'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click="deleteForwarder('{{ $forwarder['forward_to'] ?? '' }}')"
                                    wire:confirm="Delete this forwarder?"
                                >
                                    Delete
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No forwarders configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Autoresponder</h2>

            @if ($autorespondersError)
                <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $autorespondersError }}
                </div>
            @endif

            <div class="mt-3 space-y-3">
                <flux:field>
                    <flux:label>Subject</flux:label>
                    <flux:input wire:model="autoresponderSubject" placeholder="Out of office" />
                    <flux:error name="autoresponderSubject" />
                </flux:field>

                <flux:field>
                    <flux:label>Message</flux:label>
                    <flux:textarea wire:model="autoresponderBody" rows="4" />
                    <flux:error name="autoresponderBody" />
                </flux:field>

                <div class="flex items-center gap-2">
                    <flux:button variant="primary" wire:click="addAutoresponder" icon="plus">Save Autoresponder</flux:button>
                    <flux:button variant="ghost" wire:click="deleteAutoresponder" wire:confirm="Delete autoresponder for this mailbox?">Delete</flux:button>
                </div>
            </div>

            @if (count($autoresponders) > 0)
                <div class="mt-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Current Autoresponder</p>
                    <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $autoresponders[0]['subject'] ?? '—' }}</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-300">{{ $autoresponders[0]['body'] ?? '' }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Filters</h2>

            @if ($filtersError)
                <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $filtersError }}
                </div>
            @endif

            <div class="mt-3 space-y-3">
                <flux:field>
                    <flux:label>Filter Name</flux:label>
                    <flux:input wire:model="filterName" placeholder="Route From Finance" />
                    <flux:error name="filterName" />
                </flux:field>

                <flux:field>
                    <flux:label>From Contains</flux:label>
                    <flux:input wire:model="filterFromContains" placeholder="finance@vendor.com" />
                    <flux:error name="filterFromContains" />
                </flux:field>

                <flux:field>
                    <flux:label>Forward To</flux:label>
                    <flux:input wire:model="filterForwardTo" placeholder="ops@example.com" />
                    <flux:error name="filterForwardTo" />
                </flux:field>

                <flux:button variant="primary" wire:click="addFilter" icon="plus">Add Filter</flux:button>
            </div>

            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($filters as $filter)
                            <tr wire:key="filter-{{ md5(($filter['name'] ?? '').$cachedEmailAccount->id) }}">
                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $filter['name'] ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        wire:click="deleteFilter('{{ $filter['name'] ?? '' }}')"
                                        wire:confirm="Delete this filter?"
                                    >
                                        Delete
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No filters configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
