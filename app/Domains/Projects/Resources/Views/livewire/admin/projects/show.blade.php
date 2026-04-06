<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $project->name }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Project # {{ $project->project_number ?? 'N/A' }}
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

            @if (in_array('documents', $tabs, true))
                <button type="button" wire:click="setTab('documents')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'documents' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Documents
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $documentCount }}</span>
                </button>
            @endif

            @if (in_array('access', $tabs, true))
                <button type="button" wire:click="setTab('access')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'access' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    Access
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $accessAssignments->count() }}</span>
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
    @endif

    @if ($activeTab === 'dailies' && in_array('dailies', $tabs, true))
        @include('dailies::components.project-tab', [
            'project' => $project,
            'dailies' => $projectDailies,
            'dailyCount' => $dailyCount,
        ])
    @endif

    @if ($activeTab === 'tasks' && in_array('tasks', $tabs, true))
        <livewire:app.domains.tasks.livewire.admin.projects.task-hierarchy-widget :project="$project" :key="'project-task-widget-'.$project->id" />
    @endif

    @if ($activeTab === 'invoices' && in_array('invoices', $tabs, true))
        @include('invoices::components.project-tab', [
            'project' => $project,
            'invoices' => $projectInvoices,
            'invoiceCount' => $invoiceCount,
        ])
    @endif

    @if ($activeTab === 'stock' && in_array('stock', $tabs, true))
        @include('stock::components.project-tab', [
            'project' => $project,
            'stockOrders' => $projectStockOrders,
            'stockOrderCount' => $stockOrderCount,
        ])
    @endif

    @if ($activeTab === 'documents' && in_array('documents', $tabs, true))
        <livewire:app.domains.documents.livewire.admin.projects.documents-tab :project="$project" :key="'project-documents-tab-'.$project->id" />
    @endif

    @if ($activeTab === 'access' && in_array('access', $tabs, true))
        <div class="space-y-4">
            @if (auth()->user()?->hasPermission('project-access.grant'))
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="grid gap-3 md:grid-cols-[2fr_1fr]">
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

                        <div class="flex items-end">
                            <flux:button wire:click="grantProjectAccess" variant="primary" class="w-full">Grant Project Access</flux:button>
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
        </div>
    @endif
</div>
