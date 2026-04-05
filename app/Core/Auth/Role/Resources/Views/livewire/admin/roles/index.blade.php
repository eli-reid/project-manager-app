<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Role Management</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage custom roles and access levels.</p>
        </div>

        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
            New Role
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <label for="role-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Search</label>
        <input id="role-search" type="text" wire:model.live.debounce.300ms="search" placeholder="Role name or description" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Users</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $role->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $role->description ?: 'No description' }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $role->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span>
                                @if ($role->built_in)
                                    <span class="ml-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Built-in</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $role->users_count }}</td>
                            <td class="px-4 py-3 align-top">
                                <x-ui.row-actions-dropdown label="Role actions" width="w-44" :menu-height="220">
                                    <a
                                        href="{{ route('admin.roles.edit', $role) }}"
                                        class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @click="closeMenu()"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="{{ route('admin.roles.users', $role) }}"
                                        class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @click="closeMenu()"
                                    >
                                        Users
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="toggleStatus('{{ $role->id }}')"
                                        wire:confirm="Are you sure you want to {{ $role->is_active ? 'disable' : 'enable' }} this role?"
                                        wire:loading.attr="disabled"
                                        class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 disabled:opacity-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @disabled($role->built_in)
                                        @click="closeMenu()"
                                    >
                                        {{ $role->is_active ? 'Disable' : 'Enable' }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="deleteRole('{{ $role->id }}')"
                                        wire:confirm="Are you sure you want to delete this role? This action cannot be undone."
                                        wire:loading.attr="disabled"
                                        class="block w-full px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50 disabled:opacity-50 dark:text-red-300 dark:hover:bg-red-900/30"
                                        @disabled($role->built_in)
                                        @click="closeMenu()"
                                    >
                                        Delete
                                    </button>
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $roles->links() }}
        </div>
    </div>
</div>
