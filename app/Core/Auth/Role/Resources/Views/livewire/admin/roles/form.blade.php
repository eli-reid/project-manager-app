<div class="w-full space-y-6">
    @php
        $permissionGroupCount = count($permissionsByResource);
        $permissionCount = collect($permissionsByResource)->sum(fn (array $resourceData): int => count($resourceData['permissions']));
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl space-y-1">
            <flux:heading size="xl" level="1">{{ $isEdit ? 'Edit Role' : 'Create Role' }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Define the role profile, then assign permissions by resource so access is easier to scan and review.</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:badge>{{ $permissionGroupCount }} resources</flux:badge>
            <flux:badge>{{ $permissionCount }} permissions</flux:badge>
            <flux:badge>{{ count($selectedPermissionIds) }} selected</flux:badge>
            @if ($isEdit && $role)
                <a href="{{ route('admin.roles.users', $role) }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Manage Users</a>
            @endif
            <a href="{{ route('admin.roles.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
        </div>
    </div>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Role details</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">These settings control the role’s identity and access level.</flux:text>
                    </div>

                    <flux:badge>{{ $isEdit ? 'Editing' : 'Draft' }}</flux:badge>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input type="text" wire:model.live="name" :disabled="$isEdit && $role?->built_in" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Access Level</flux:label>
                        <flux:input type="number" min="0" max="100" wire:model.live="access_level" :disabled="$isEdit && $role?->built_in" />
                        <flux:error name="access_level" />
                    </flux:field>

                    <flux:field class="md:col-span-2">
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model.live="description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>

                    <flux:field class="md:col-span-2">
                        <flux:checkbox wire:model.live="is_active" :disabled="$isEdit && $role?->built_in" label="Active" />
                        <flux:error name="is_active" />
                    </flux:field>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Permissions</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Permissions are grouped by resource and each item includes a short explanation.</flux:text>
                    </div>

                    <flux:badge>{{ count($selectedPermissionIds) }} selected</flux:badge>
                </div>

                <div class="mt-5 grid gap-4">
                    @foreach ($permissionsByResource as $resource => $resourceData)
                        @php
                            $resourcePermissionCount = count($resourceData['permissions']);
                        @endphp
                        <section class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/40" wire:key="resource-{{ $resource }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">{{ $resourceData['name'] }}</h3>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $resourcePermissionCount }} permissions in this group</p>
                                </div>

                                <flux:badge>{{ $resourcePermissionCount }}</flux:badge>
                            </div>

                            <div class="mt-4 grid gap-2 lg:grid-cols-2">
                                @foreach ($resourceData['permissions'] as $permission)
                                    <label class="group flex h-full items-start gap-3 rounded-xl border border-white/70 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm transition hover:-translate-y-px hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700/80 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/80" wire:key="permission-{{ $permission->id }}">
                                        <input type="checkbox" value="{{ $permission->id }}" wire:model.live="selectedPermissionIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700" />
                                        <span class="min-w-0">
                                            @php
                                                $action = $permission->action;
                                                $scopeTag = null;

                                                if (str_contains($action, 'view-all') || str_contains($action, 'view-any') || str_contains($action, 'approve') || str_contains($action, 'reject')) {
                                                    $scopeTag = 'All users';
                                                } elseif (str_contains($action, 'view') || str_contains($action, 'edit') || str_contains($action, 'update') || str_contains($action, 'delete') || str_contains($action, 'submit') || str_contains($action, 'create')) {
                                                    $scopeTag = 'Own items only';
                                                }

                                                if (in_array($action, ['manage-project', 'promote-global', 'demote-private'])) {
                                                    $scopeTag = 'Own items only';
                                                }
                                            @endphp

                                            <span class="flex flex-wrap items-center gap-2 font-medium text-zinc-900 dark:text-zinc-100">
                                                <span>{{ $permission->label }}</span>
                                                @if ($scopeTag)
                                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
                                                        {{ $scopeTag }}
                                                    </span>
                                                @endif
                                            </span>

                                            <span class="mt-1 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                                {{ $permission->description ?: 'Allows users to '.str($permission->action)->replace(['_', '-'], ' ')->lower().' '.str($permission->resource)->replace(['_', '-'], ' ')->lower().'.' }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                @error('selectedPermissionIds') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('selectedPermissionIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">Selection summary</flux:heading>

                <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <div class="flex items-center justify-between gap-3">
                        <span>Selected permissions</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ count($selectedPermissionIds) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span>Permission groups</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $permissionGroupCount }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span>Total permissions</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $permissionCount }}</span>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-300">
                    Built-in roles keep their active state and access level locked so the access model stays consistent.
                </div>
            </section>

            <div class="flex justify-end xl:justify-stretch">
                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="save">
                    {{ $isEdit ? 'Save Role' : 'Create Role' }}
                </flux:button>
            </div>
        </aside>
    </form>
</div>
