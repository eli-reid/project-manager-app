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

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex min-w-max gap-2">
            <button type="button" wire:click="setTab('overview')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'overview' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">Overview</button>

            @if (in_array('dailies', $tabs, true))
                <button type="button" wire:click="setTab('dailies')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'dailies' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Dailies
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $dailyCount }}</span>
                </button>
            @endif

            @if (in_array('tasks', $tabs, true))
                <button type="button" wire:click="setTab('tasks')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'tasks' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">Tasks</button>
            @endif

            @if (in_array('invoices', $tabs, true))
                <button type="button" wire:click="setTab('invoices')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'invoices' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Invoices
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $invoiceCount }}</span>
                </button>
            @endif

            @if (in_array('stock', $tabs, true))
                <button type="button" wire:click="setTab('stock')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'stock' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Stock
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $stockOrderCount }}</span>
                </button>
            @endif

            @if (in_array('submittals', $tabs, true))
                <button type="button" wire:click="setTab('submittals')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'submittals' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Submittals
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $submittalCount }}</span>
                </button>
            @endif

            @if (in_array('change-orders', $tabs, true))
                <button type="button" wire:click="setTab('change-orders')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'change-orders' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Change Orders
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $changeOrderCount }}</span>
                </button>
            @endif

            @if (in_array('rfis', $tabs, true))
                <button type="button" wire:click="setTab('rfis')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'rfis' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    RFIs
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $rfiCount }}</span>
                </button>
            @endif

            @if (in_array('documents', $tabs, true))
                <button type="button" wire:click="setTab('documents')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'documents' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Library
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $documentCount }}</span>
                </button>
            @endif

            @if (in_array('access', $tabs, true))
                <button type="button" wire:click="setTab('access')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'access' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Access
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $accessAssignments->count() + $roleAccessAssignments->count() }}</span>
                </button>
            @endif

            @if (in_array('time', $tabs, true))
                <button type="button" wire:click="setTab('time')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'time' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Time
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $timeEntryCount }}</span>
                </button>
            @endif

            @if (in_array('financials', $tabs, true))
                <button type="button" wire:click="setTab('financials')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'financials' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Financials
                </button>
            @endif
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
        <livewire:invoices::admin.projects.project-tab
            :project="$project"
            :invoices="$projectInvoices"
            :invoice-count="$invoiceCount"
            :key="'project-invoices-tab-'.$project->id"
        />
    @endif

    @if ($activeTab === 'stock' && in_array('stock', $tabs, true))
        <livewire:stock::admin.projects.project-tab
            :project="$project"
            :stock-orders="$projectStockOrders"
            :stock-order-count="$stockOrderCount"
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
