<div class="space-y-4">
    @if (auth()->user()?->hasPermission('project-access.grant'))
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading size="sm">Grant User Access</flux:heading>
                    <flux:field>
                        <flux:label>Grant Access To User</flux:label>
                        <flux:select wire:model.live="selectedAccessUserId">
                            <option value="">Select a user</option>
                            @foreach ($assignableUsers as $assignableUser)
                                <option value="{{ $assignableUser->id }}">{{ trim($assignableUser->first_name.' '.$assignableUser->last_name) }} ({{ $assignableUser->email }})</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedAccessUserId" />
                    </flux:field>

                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($availableAccessPermissionOptions as $permissionKey => $permissionLabel)
                            <flux:field>
                                <flux:checkbox wire:model.live="selectedAccessPermissionKeys" value="{{ $permissionKey }}" :label="$permissionLabel" />
                            </flux:field>
                        @endforeach
                    </div>
                    <flux:error name="selectedAccessPermissionKeys" />
                    <flux:error name="selectedAccessPermissionKeys.*" />

                    <flux:button wire:click="grantProjectAccess" variant="primary" class="w-full">Grant User Access</flux:button>
                </div>

                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading size="sm">Grant Role Access</flux:heading>
                    <flux:field>
                        <flux:label>Grant Access To Role</flux:label>
                        <flux:select wire:model.live="selectedAccessRoleId">
                            <option value="">Select a role</option>
                            @foreach ($assignableRoles as $assignableRole)
                                <option value="{{ $assignableRole->id }}">{{ $assignableRole->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedAccessRoleId" />
                    </flux:field>

                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($availableAccessPermissionOptions as $permissionKey => $permissionLabel)
                            <flux:field>
                                <flux:checkbox wire:model.live="selectedAccessPermissionKeys" value="{{ $permissionKey }}" :label="$permissionLabel" />
                            </flux:field>
                        @endforeach
                    </div>

                    <flux:button wire:click="grantProjectRoleAccess" variant="primary" class="w-full">Grant Role Access</flux:button>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Granted By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Granted At</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($accessAssignments as $assignment)
                        <tr wire:key="project-access-assignment-{{ $assignment->id }}">
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ trim(($assignment->user?->first_name ?? '').' '.($assignment->user?->last_name ?? '')) ?: 'Unknown User' }}
                                @if ($assignment->user?->email)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $assignment->user->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ trim(($assignment->grantedBy?->first_name ?? '').' '.($assignment->grantedBy?->last_name ?? '')) ?: 'System' }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $assignment->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right align-top">
                                <div class="mb-2 flex flex-wrap justify-end gap-1">
                                    @foreach (($assignment->permission_keys ?? []) as $permissionKey)
                                        <span class="inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $permissionKey }}</span>
                                    @endforeach
                                </div>
                                @if (auth()->user()?->hasPermission('project-access.revoke'))
                                    <flux:button size="sm" variant="ghost" wire:click="revokeProjectAccess('{{ $assignment->user_id }}')">Revoke</flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No explicit user access assignments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Granted By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Granted At</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($roleAccessAssignments as $roleAssignment)
                        <tr wire:key="project-role-access-assignment-{{ $roleAssignment->id }}">
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $roleAssignment->role?->name ?? 'Unknown Role' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ trim(($roleAssignment->grantedBy?->first_name ?? '').' '.($roleAssignment->grantedBy?->last_name ?? '')) ?: 'System' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $roleAssignment->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right align-top">
                                <div class="mb-2 flex flex-wrap justify-end gap-1">
                                    @foreach (($roleAssignment->permission_keys ?? []) as $permissionKey)
                                        <span class="inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $permissionKey }}</span>
                                    @endforeach
                                </div>

                                @if (auth()->user()?->hasPermission('project-access.revoke'))
                                    <flux:button size="sm" variant="ghost" wire:click="revokeProjectRoleAccess('{{ $roleAssignment->role_id }}')">Revoke</flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No explicit role access assignments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
