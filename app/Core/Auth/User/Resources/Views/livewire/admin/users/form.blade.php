<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit User' : 'Create User' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage account details and role assignments.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">First Name</label>
                <input type="text" wire:model.live="first_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Last Name</label>
                <input type="text" wire:model.live="last_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Username</label>
                <input type="text" wire:model.live="username" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Email</label>
                <input type="email" wire:model.live="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($isEdit)
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Password (optional)</label>
                    <input type="password" wire:model.live="password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Confirm Password</label>
                    <input type="password" wire:model.live="password_confirmation" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                </div>
            @else
                <div class="md:col-span-2">
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-100">
                        <p class="font-semibold">Invitation-based setup</p>
                        <p class="mt-1 text-sky-800 dark:text-sky-200">A temporary password will be generated automatically and emailed to this user after you create the account.</p>
                    </div>
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="is_active" class="rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                    Active
                </label>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Roles</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($roles as $role)
                    <label class="flex items-start gap-2 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300" wire:key="role-option-{{ $role->id }}">
                        <input type="checkbox" value="{{ $role->id }}" wire:model.live="selectedRoleIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                        <span>
                            <span class="font-semibold">{{ $role->name }}</span>
                            @if ($role->description)
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $role->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            @error('selectedRoleIds') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('selectedRoleIds.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $isEdit ? 'Save User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>