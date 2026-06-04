<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $project->name }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Project # {{ $project->project_number ?? 'N/A' }}
            </flux:text>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Accounting Code: {{ $project->accounting_code ?? 'N/A' }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.projects.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
            @can('update', $project)
                <a href="{{ route('admin.projects.edit', $project) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
            @endcan
        </div>
    </div>

    <div x-data="{ manageTabs: false }" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-3 px-1">
            <flux:text class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Project Tabs</flux:text>
            @if (count($visibleTabItems) > 1 || count($hiddenTabItems) > 0)
                <button type="button" @click="manageTabs = ! manageTabs" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    <span x-show="! manageTabs">Customize Tabs</span>
                    <span x-show="manageTabs">Done</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <div class="flex min-w-max gap-2">
                @foreach ($visibleTabItems as $tabItem)
                    <button type="button" wire:key="project-tab-button-{{ $tabItem['key'] }}" wire:click="setTab('{{ $tabItem['key'] }}')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === $tabItem['key'] ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                        {{ $tabItem['label'] }}
                        @if (array_key_exists($tabItem['key'], $tabBadges))
                            <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $tabBadges[$tabItem['key']] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div x-cloak x-show="manageTabs" class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-950/40">
            <div class="space-y-3">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Visible Tabs</flux:text>
                    <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Drag to reorder. Hidden tabs stay available below for quick restore.</flux:text>
                </div>

                <div wire:sort="sortProjectTab" class="flex flex-wrap gap-2">
                    @foreach ($visibleTabItems as $tabItem)
                        <div wire:key="project-tab-sort-item-{{ $tabItem['key'] }}" wire:sort:item="{{ $tabItem['key'] }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-2 text-sm text-zinc-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <button type="button" wire:sort:handle class="rounded-md border border-zinc-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">Move</button>
                            <span>{{ $tabItem['label'] }}</span>
                            @if ($tabItem['key'] !== 'overview')
                                <button type="button" wire:click="hideTab('{{ $tabItem['key'] }}')" class="rounded-md border border-zinc-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">Hide</button>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($hiddenTabItems !== [])
                    <div class="space-y-2 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Hidden Tabs</flux:text>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($hiddenTabItems as $tabItem)
                                <div wire:key="project-hidden-tab-item-{{ $tabItem['key'] }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-2 text-sm text-zinc-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    <span>{{ $tabItem['label'] }}</span>
                                    <button type="button" wire:click="showTab('{{ $tabItem['key'] }}')" class="rounded-md border border-zinc-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">Show</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($activeTab === 'overview')
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->status?->label() ?? 'Unknown' }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Timeline</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $project->start_date?->format('M j, Y') ?? 'TBD' }}
                    <span class="mx-1">to</span>
                    {{ $project->end_date?->format('M j, Y') ?? 'TBD' }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Task Count</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $taskCount }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job Site Address</p>
            </div>
            @if ($projectAddress)
                <div class="space-y-0.5 px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                    <p>{{ $projectAddress->address1 }}</p>
                    @if ($projectAddress->address2)
                        <p>{{ $projectAddress->address2 }}</p>
                    @endif
                    <p>
                        {{ $projectAddress->city }}@if ($projectAddress->city && $projectAddress->state),@endif
                        {{ $projectAddress->state }}
                        {{ $projectAddress->zip }}
                    </p>
                    @if ($projectAddress->country && $projectAddress->country !== 'US')
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $projectAddress->country }}</p>
                    @endif
                </div>
            @else
                <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">No address on file. <a href="{{ route('admin.projects.edit', $project) }}" class="underline hover:text-zinc-700 dark:hover:text-zinc-200" wire:navigate>Edit project</a> to add one.</p>
            @endif
        </div>
    @endif

    @if ($activeTab === 'dailies' && in_array('dailies', $tabs, true))
        <livewire:dailies::admin.projects.project-tab
            :project="$project"
            :dailies="$projectDailies"
            :daily-count="$dailyCount"
            :key="'project-dailies-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'tasks' && in_array('tasks', $tabs, true))
        <livewire:tasks::admin.projects.task-hierarchy-widget :project="$project" :key="'project-task-widget-'.$project->id.'-'.$taskWidgetVersion" />
    @endif

    @if ($activeTab === 'invoices' && in_array('invoices', $tabs, true))
        <livewire:invoices::admin.invoices.index
            :project="$project"
            :embedded="true"
            :key="'project-invoices-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'stock' && in_array('stock', $tabs, true))
        <livewire:stock::admin.stock-orders.index
            :project="$project"
            :embedded="true"
            :key="'project-stock-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'submittals' && in_array('submittals', $tabs, true))
        <livewire:submittals::admin.projects.project-tab
            :project="$project"
            :submittals="$projectSubmittals"
            :submittal-count="$submittalCount"
            :key="'project-submittals-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'change-orders' && in_array('change-orders', $tabs, true))
        <livewire:change-orders::admin.projects.project-tab
            :project="$project"
            :change-orders="$projectChangeOrders"
            :change-order-count="$changeOrderCount"
            :key="'project-change-orders-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'rfis' && in_array('rfis', $tabs, true))
        <livewire:rfis::admin.projects.project-tab
            :project="$project"
            :rfis="$projectRfis"
            :rfi-count="$rfiCount"
            :is-create-mode="$isRfiCreateMode"
            :key="'project-rfis-tab-'.$project->id.($isRfiCreateMode ? '-create' : '')"
        />
    @endif

    @if ($activeTab === 'documents' && in_array('documents', $tabs, true))
        <livewire:documents::admin.projects.documents-tab :project="$project" :key="'project-documents-tab-'.$project->id" />
    @endif

    @if ($activeTab === 'access' && in_array('access', $tabs, true))
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
    @endif
    @if ($activeTab === 'time' && in_array('time', $tabs, true))
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Hours</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalHours, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Regular</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($regularHours, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Overtime</p>
                <p class="mt-2 text-2xl font-bold {{ $overtimeHours > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($overtimeHours, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Double Time</p>
                <p class="mt-2 text-2xl font-bold {{ $doubleTimeHours > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($doubleTimeHours, 2) }}</p>
            </div>
        </div>

        @if ($hoursByUser->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Hours by Employee</p>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($hoursByUser as $row)
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->user?->name ?? 'Unknown' }}</span>
                            <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">{{ number_format((float) $row->total_hours, 2) }}h</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recent Time Entries</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hours</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Cost Code</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($recentTimeEntries as $entry)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $entry->date?->format('M j, Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $entry->user?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format((float) $entry->hours, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($entry->costCode)
                                        <span class="font-mono">{{ $entry->costCode->code }}</span>
                                        <span class="ml-1 text-xs">{{ $entry->costCode->name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="max-w-xs truncate px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $entry->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No time entries for this project.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($activeTab === 'financials' && in_array('financials', $tabs, true))
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Budget</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $financialSummary['budget'] !== null ? '$'.number_format($financialSummary['budget'], 2) : '—' }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Invoiced</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($financialSummary['invoiced'], 2) }}
                    <span class="ml-1 text-xs text-zinc-400 dark:text-zinc-500">({{ $financialSummary['invoice_count'] }} {{ Str::plural('invoice', $financialSummary['invoice_count']) }})</span>
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Remaining Budget</p>
                <p class="mt-2 text-sm font-medium {{ $financialSummary['remaining'] !== null && $financialSummary['remaining'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                    {{ $financialSummary['remaining'] !== null ? '$'.number_format($financialSummary['remaining'], 2) : '—' }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Budget Used</p>
                <p class="mt-2 text-sm font-medium {{ $financialSummary['variance_pct'] !== null && $financialSummary['variance_pct'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                    {{ $financialSummary['variance_pct'] !== null ? $financialSummary['variance_pct'].'%' : '—' }}
                </p>
            </div>
        </div>

        @if (in_array('time', $tabs, true))
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Hours Logged</p>
                    <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($totalHours, 2) }}h</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Regular Hours</p>
                    <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($regularHours, 2) }}h</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Overtime Hours</p>
                    <p class="mt-2 text-sm font-medium {{ $overtimeHours > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ number_format($overtimeHours, 2) }}h</p>
                </div>
            </div>
        @endif
    @endif</div>
