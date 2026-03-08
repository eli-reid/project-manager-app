<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Role Users: {{ $role->name }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Assign and remove users for this role.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Assigned Users</h2>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $assignedUsers->count() }}</span>
            </div>

            <input type="text" wire:model.live.debounce.300ms="searchAssigned" placeholder="Search assigned users" class="mb-4 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />

            <div class="space-y-2">
                @forelse ($assignedUsers as $user)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" wire:key="assigned-{{ $user->id }}">
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $user->first_name }} {{ $user->last_name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ '@'.$user->username }} • {{ $user->email }}</div>
                        </div>
                        <button type="button" wire:click="removeUser('{{ $user->id }}')" class="rounded-md border border-red-300 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20">Remove</button>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No users currently assigned.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Assign Users</h2>

            <input type="text" wire:model.live.debounce.300ms="searchAvailable" placeholder="Search available users" class="mb-4 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />

            <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                @forelse ($availableUsers as $user)
                    <label class="flex items-start gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300" wire:key="available-{{ $user->id }}">
                        <input type="checkbox" value="{{ $user->id }}" wire:model.live="selectedUserIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                        <span>
                            <span class="font-semibold">{{ $user->first_name }} {{ $user->last_name }}</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ '@'.$user->username }} • {{ $user->email }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No available users match your search.</p>
                @endforelse
            </div>

            @error('selectedUserIds') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('selectedUserIds.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 flex justify-end">
                <button type="button" wire:click="assignSelectedUsers" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Assign Selected</button>
            </div>
        </section>
    </div>
</div>
