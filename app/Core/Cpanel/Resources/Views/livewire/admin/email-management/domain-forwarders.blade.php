<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Domain Forwarders</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Route all mail from one domain to another destination domain.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.dashboard')" icon="arrow-left" wire:navigate>Back to Dashboard</flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Add Domain Forwarder</h2>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <flux:field>
                <flux:label>Source Domain</flux:label>
                <flux:input wire:model="sourceDomain" placeholder="example.com" />
                <flux:error name="sourceDomain" />
            </flux:field>

            <flux:field>
                <flux:label>Destination Domain</flux:label>
                <flux:input wire:model="destinationDomain" placeholder="example.org" />
                <flux:error name="destinationDomain" />
            </flux:field>
        </div>

        <div class="mt-4">
            <flux:button variant="primary" wire:click="addDomainForwarder" icon="plus">Add Domain Forwarder</flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Configured Domain Forwarders</h2>

        @if ($domainForwardersError)
            <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                {{ $domainForwardersError }}
            </div>
        @endif

        <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Domain</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Destination</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($domainForwarders as $forwarder)
                        <tr wire:key="domain-forwarder-{{ md5(($forwarder['domain'] ?? '').($forwarder['destination'] ?? '')) }}">
                            <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $forwarder['domain'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $forwarder['destination'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click="deleteDomainForwarder('{{ $forwarder['domain'] ?? '' }}')"
                                    wire:confirm="Delete this domain forwarder?"
                                >
                                    Delete
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No domain forwarders configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
