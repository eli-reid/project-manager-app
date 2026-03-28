@php
    $categoryId = (string) $category->id;
    $categoryTasks = $tasksByCategory->get($categoryId, collect());
    $summary = $categorySummaries[$categoryId] ?? [
        'taskCount' => 0,
        'completedTaskCount' => 0,
        'progressPercent' => 0,
        'ancestorVisibilityCondition' => 'true',
        'childrenVisibilityCondition' => "!isCollapsed('{$categoryId}')",
    ];
    $categoryIndent = ($depth * 18) + 12;
    $taskIndent = (($depth + 1) * 18) + 20;
    $subTaskIndent = (($depth + 2) * 18) + 28;
    $progressWidth = $summary['progressPercent'].'%';
@endphp

<tr class="bg-zinc-50/70 dark:bg-zinc-800/50" x-show="{{ $summary['ancestorVisibilityCondition'] }}" x-cloak wire:key="category-row-{{ $categoryId }}">
    <td class="px-3 py-2 align-top text-sm font-semibold text-zinc-900 dark:text-zinc-100" @style(["padding-left: {$categoryIndent}px"] )>
        <button type="button" @click="toggleCategory('{{ $categoryId }}')" class="inline-flex items-center gap-2 rounded-md px-1 py-1 hover:bg-zinc-200/70 dark:hover:bg-zinc-700/70">
            <svg class="h-3.5 w-3.5 text-zinc-500 transition-transform" :class="isCollapsed('{{ $categoryId }}') ? '' : 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" />
            </svg>
            <span>{{ $category->name }}</span>
        </button>
    </td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">{{ $summary['taskCount'] }} items</td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">Category</td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">
        <div class="w-full max-w-40">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400" @style(["width: {$progressWidth}"] )></div>
            </div>
            <div class="mt-1">{{ $summary['progressPercent'] }}% complete ({{ $summary['completedTaskCount'] }}/{{ $summary['taskCount'] }})</div>
        </div>
    </td>
    <td class="px-3 py-2 align-top text-right">
        <div class="relative inline-block text-left" x-data="buildMenuState(150)" @click.away="closeMenu()">
            <button type="button" @click="toggleMenu($event)" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Category actions">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <circle cx="4" cy="10" r="1.5" />
                    <circle cx="10" cy="10" r="1.5" />
                    <circle cx="16" cy="10" r="1.5" />
                </svg>
            </button>

            <div x-show="open" x-cloak class="fixed z-30 w-44 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :style="menuStyle">
                <button type="button" @click="open = false" wire:click="startInlineTaskForm('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Task</button>
                <button type="button" @click="open = false" wire:click="startInlineCategoryForm('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Subcategory</button>
                <button type="button" @click="open = false" wire:click="copyCategoryFrom('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category</button>
                <button type="button" @click="open = false; showCopyModal = true" wire:click="$set('copySourceCategoryId', '{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category Tasks</button>
                @can('delete', $category)
                    <button
                        type="button"
                        @click="open = false"
                        wire:click="deleteCategory('{{ $categoryId }}')"
                        wire:confirm="Delete this category branch? This deletes the category, all subcategories, and all tasks in that branch."
                        class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
                    >
                        Delete Category
                    </button>
                @endcan
            </div>
        </div>
    </td>
</tr>

@foreach ($categoryTasks as $task)
    <tr x-show="{{ $summary['childrenVisibilityCondition'] }}" x-cloak wire:key="task-row-{{ $task->id }}">
        <td class="px-3 py-2 align-top text-sm text-zinc-800 dark:text-zinc-200" @style(["padding-left: {$taskIndent}px"] )>
            {{ $task->title }}
        </td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">Task</td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ str($task->status)->replace('_', ' ')->headline() }}</td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ $task->assignedTo ? $task->assignedTo->first_name.' '.$task->assignedTo->last_name : '—' }}</td>
        <td class="px-3 py-2 align-top text-right">
            <div class="relative inline-block text-left" x-data="buildMenuState(120)" @click.away="closeMenu()">
                <button type="button" @click="toggleMenu($event)" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Task actions">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <circle cx="4" cy="10" r="1.5" />
                        <circle cx="10" cy="10" r="1.5" />
                        <circle cx="16" cy="10" r="1.5" />
                    </svg>
                </button>
                <div x-show="open" x-cloak class="fixed z-30 w-40 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :style="menuStyle">
                    @can('update', $task)
                        <a href="{{ route('admin.tasks.edit', $task) }}" wire:navigate class="block px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit Task</a>
                    @endcan
                    @can('create', \App\Domains\Tasks\Models\Task::class)
                        <button type="button" @click="open = false" wire:click="copyTaskFrom('{{ $task->id }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Task</button>
                    @endcan
                    @can('delete', $task)
                        <button type="button" @click="open = false" wire:click="deleteTask('{{ $task->id }}')" wire:confirm="Delete this task?" class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30">Delete Task</button>
                    @endcan
                </div>
            </div>
        </td>
    </tr>

    @foreach ($task->subTasks as $subTask)
        <tr x-show="{{ $summary['childrenVisibilityCondition'] }}" x-cloak wire:key="subtask-row-{{ $subTask->id }}">
            <td class="px-3 py-2 align-top text-sm text-zinc-700 dark:text-zinc-300" @style(["padding-left: {$subTaskIndent}px"] )>
                -> {{ $subTask->title }}
            </td>
            <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">Subtask</td>
            <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ str($subTask->status)->replace('_', ' ')->headline() }}</td>
            <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ $subTask->assignedTo ? $subTask->assignedTo->first_name.' '.$subTask->assignedTo->last_name : '—' }}</td>
            <td class="px-3 py-2 align-top text-right">
                <div class="relative inline-block text-left" x-data="buildMenuState(120)" @click.away="closeMenu()">
                    <button type="button" @click="toggleMenu($event)" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Task actions">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <circle cx="4" cy="10" r="1.5" />
                            <circle cx="10" cy="10" r="1.5" />
                            <circle cx="16" cy="10" r="1.5" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="fixed z-30 w-40 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :style="menuStyle">
                        @can('update', $subTask)
                            <a href="{{ route('admin.tasks.edit', $subTask) }}" wire:navigate class="block px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit Task</a>
                        @endcan
                        @can('create', \App\Domains\Tasks\Models\Task::class)
                            <button type="button" @click="open = false" wire:click="copyTaskFrom('{{ $subTask->id }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Task</button>
                        @endcan
                        @can('delete', $subTask)
                            <button type="button" @click="open = false" wire:click="deleteTask('{{ $subTask->id }}')" wire:confirm="Delete this task?" class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30">Delete Task</button>
                        @endcan
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
@endforeach

@foreach ($category->childrenRecursive as $child)
    @include('tasks::livewire.admin.projects._task-category-tree-row', [
        'category' => $child,
        'depth' => $depth + 1,
        'tasksByCategory' => $tasksByCategory,
        'categorySummaries' => $categorySummaries,
    ])
@endforeach