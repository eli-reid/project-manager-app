<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit Role' : 'Create Role' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Define role details and assign permissions.</p>
        </div>
        <div class="flex items-center gap-2">
            @if ($isEdit && $role)
                <a href="{{ route('admin.roles.users', $role) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Manage Users</a>
            @endif
            <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</label>
                <input type="text" wire:model.live="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Access Level</label>
                <input type="number" min="0" max="100" wire:model.live="access_level" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($isEdit && $role?->built_in) />
                @error('access_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                <textarea wire:model.live="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="is_active" class="rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" @disabled($isEdit && $role?->built_in) />
                    Active
                </label>
                @error('is_active') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Permissions</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($permissionsByResource as $resource => $resourceData)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="resource-{{ $resource }}">
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">{{ $resourceData['name'] }}</h3>
                        <div class="space-y-2">
                            @foreach ($resourceData['permissions'] as $permission)
                                <label class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300" wire:key="permission-{{ $permission->id }}">
                                    <input type="checkbox" value="{{ $permission->id }}" wire:model.live="selectedPermissionIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                                    <span>
                                        <span class="font-medium flex items-center gap-2">
                                            {{ $permission->label }}
                                            @php
                                                // Determine scope tag based on action naming and resource
                                                $action = $permission->action;
                                                $scopeTag = null;
                                                if (str_contains($action, 'view-all') || str_contains($action, 'view-any') || str_contains($action, 'approve') || str_contains($action, 'reject')) {
                                                    $scopeTag = 'All users';
                                                } elseif (str_contains($action, 'view') || str_contains($action, 'edit') || str_contains($action, 'update') || str_contains($action, 'delete') || str_contains($action, 'submit') || str_contains($action, 'create')) {
                                                    $scopeTag = 'Own items only';
                                                }
                                                // Special cases for project/document/stock domains
                                                if (in_array($action, ['manage-project', 'promote-global', 'demote-private'])) {
                                                    $scopeTag = 'Own items only';
                                                }
                                            @endphp
                                            @if ($scopeTag)
                                                <span class="ml-2 inline-flex items-center rounded bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
                                                    {{ $scopeTag }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $permission->description ?: 'Allows users to '.str($permission->action)->replace(['_', '-'], ' ')->lower().' '.str($permission->resource)->replace(['_', '-'], ' ')->lower().'.' }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('selectedPermissionIds') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('selectedPermissionIds.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $isEdit ? 'Save Role' : 'Create Role' }}
            </button>
        </div>
    </form>
</div>
