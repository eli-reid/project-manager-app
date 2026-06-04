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
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $tabBadges['tasks'] ?? 0 }}</p>
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

    @foreach ($tabPanels as $tabPanel)
        @if ($activeTab === $tabPanel['tab'] && in_array($tabPanel['tab'], $tabs, true))
            @livewire($tabPanel['component'], $tabPanel['props'], key($tabPanel['key']))
        @endif
    @endforeach
</div>
