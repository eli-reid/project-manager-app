<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Addresses</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage project and client site addresses.</p>
        </div>
        @can('create', \App\Domains\Addresses\Models\Address::class)
            <a href="{{ route('admin.addresses.create') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Address</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Client</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($addresses as $address)
                        <tr wire:key="address-{{ $address->id }}">
                            <td class="px-4 py-3 align-top text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $address->address1 }}
                                @if ($address->city || $address->state)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$address->city, $address->state, $address->zip])->filter()->implode(', ') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $address->client?->company_name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 align-top">
                                <x-ui.row-actions-dropdown label="Address actions" width="w-36" :menu-height="130">
                                    @can('update', $address)
                                        <a href="{{ route('admin.addresses.edit', $address) }}" class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                    @endcan
                                    @can('delete', $address)
                                        <button type="button" wire:click="deleteAddress('{{ $address->id }}')" wire:confirm="Delete this address?" class="block w-full px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30" @click="closeMenu()">Delete</button>
                                    @endcan
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No addresses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $addresses->links() }}
        </div>
    </div>
</div>
