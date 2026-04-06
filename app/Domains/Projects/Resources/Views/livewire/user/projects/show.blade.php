<section class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $project->name }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Project #: :number', ['number' => $project->project_number ?? 'N/A']) }}
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('projects.index')">
            {{ __('Back to Projects') }}
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->status?->label() ?? 'Unknown' }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Timeline') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $project->start_date?->format('M j, Y') ?? 'TBD' }}
                <span class="mx-1">{{ __('to') }}</span>
                {{ $project->end_date?->format('M j, Y') ?? 'TBD' }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Client') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->client?->name ?? '—' }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Manager') }}</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->projectManager?->name ?? '—' }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex min-w-max gap-2">
            <button type="button" wire:click="setTab('overview')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'overview' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">{{ __('Overview') }}</button>

            @if (in_array('dailies', $tabs, true))
                <button type="button" wire:click="setTab('dailies')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'dailies' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ __('Dailies') }}
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $dailyCount }}</span>
                </button>
            @endif

            @if (in_array('tasks', $tabs, true))
                <button type="button" wire:click="setTab('tasks')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'tasks' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ __('Tasks') }}
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $taskCount }}</span>
                </button>
            @endif

            @if (in_array('invoices', $tabs, true))
                <button type="button" wire:click="setTab('invoices')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'invoices' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ __('Invoices') }}
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $invoiceCount }}</span>
                </button>
            @endif

            @if (in_array('stock', $tabs, true))
                <button type="button" wire:click="setTab('stock')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'stock' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ __('Stock') }}
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $stockOrderCount }}</span>
                </button>
            @endif

            @if (in_array('documents', $tabs, true))
                <button type="button" wire:click="setTab('documents')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $activeTab === 'documents' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ __('Documents') }}
                    <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $documentCount }}</span>
                </button>
            @endif
        </div>
    </div>

    @if ($activeTab === 'overview')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Daily Reports') }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $dailyCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Tasks') }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $taskCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Invoices') }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $invoiceCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Stock Orders') }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $stockOrderCount }}</p>
            </div>
        </div>
    @endif

    @if ($activeTab === 'tasks' && in_array('tasks', $tabs, true))
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Recent Tasks') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('tasks.index', ['projectFilter' => $project->id])">{{ __('View All Tasks') }}</flux:button>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($recentTasks as $task)
                    <div class="px-4 py-3">
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $task->title }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ str($task->status)->replace('_', ' ')->headline() }}
                            <span class="mx-1">•</span>
                            {{ ucfirst($task->priority) }}
                            @if ($task->assignedTo)
                                <span class="mx-1">•</span>
                                {{ $task->assignedTo->name }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tasks available for this project.') }}</div>
                @endforelse
            </div>
        </div>
    @endif

    @if ($activeTab === 'dailies' && in_array('dailies', $tabs, true))
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Recent Dailies') }}</flux:heading>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($recentDailies as $daily)
                    <div class="px-4 py-3">
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $daily->report_date?->format('M j, Y') ?? '—' }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ str($daily->status)->replace('_', ' ')->headline() }}
                            <span class="mx-1">•</span>
                            {{ $daily->total_hours !== null ? number_format((float) $daily->total_hours, 2) : '0.00' }}h
                            @if ($daily->user)
                                <span class="mx-1">•</span>
                                {{ $daily->user->name }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No dailies available for this project.') }}</div>
                @endforelse
            </div>
        </div>
    @endif

    @if ($activeTab === 'invoices' && in_array('invoices', $tabs, true))
        @include('invoices::components.project-tab', [
            'project' => $project,
            'invoices' => $recentInvoices,
            'invoiceCount' => $invoiceCount,
        ])
    @endif

    @if ($activeTab === 'stock' && in_array('stock', $tabs, true))
        @include('stock::components.project-tab', [
            'project' => $project,
            'stockOrders' => $recentStockOrders,
            'stockOrderCount' => $stockOrderCount,
        ])
    @endif

    @if ($activeTab === 'documents' && in_array('documents', $tabs, true))
        <livewire:app.domains.documents.livewire.admin.projects.documents-tab :project="$project" :key="'user-project-documents-tab-'.$project->id" />
    @endif
</section>
