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

<tr
    class="bg-zinc-50/70 dark:bg-zinc-800/50"
    x-show="{{ $summary['ancestorVisibilityCondition'] }}"
    x-cloak
    wire:key="category-row-{{ $categoryId }}"
    @contextmenu.prevent.stop="openContextMenu($event, {
        type: 'category',
        id: '{{ $categoryId }}',
        canUpdate: @js(auth()->user()?->can('update', $category)),
        canDelete: @js(auth()->user()?->can('delete', $category)),
        canCreateTask: @js(auth()->user()?->can('create', \App\Domains\Tasks\Models\Task::class)),
    })"
>
    <td class="px-3 py-2 align-top text-sm font-semibold text-zinc-900 dark:text-zinc-100" @style(["padding-left: {$categoryIndent}px"] )>
        <div class="inline-flex items-center gap-2">
            <button type="button" @click="toggleCategory('{{ $categoryId }}')" class="inline-flex items-center justify-center rounded-md p-1 hover:bg-zinc-200/70 dark:hover:bg-zinc-700/70" aria-label="Toggle category">
                <svg class="h-3.5 w-3.5 text-zinc-500 transition-transform" :class="isCollapsed('{{ $categoryId }}') ? '' : 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" />
                </svg>
            </button>
            @if ($editingCategoryName === $categoryId && auth()->user()?->can('update', $category))
                <form wire:submit="saveCategoryName" class="flex items-center gap-1">
                    <input
                        type="text"
                        wire:model="editingCategoryNameValue"
                        wire:keydown.escape="cancelEditCategoryName"
                        class="rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm font-semibold text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        x-init="$el.focus(); $el.select()"
                    />
                    <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">Save</button>
                    <button type="button" wire:click="cancelEditCategoryName" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Cancel</button>
                </form>
            @else
                <span @if(auth()->user()?->can('update', $category)) @dblclick="$wire.startEditCategoryName('{{ $categoryId }}')" title="Double-click to rename" @endif class="cursor-default">{{ $category->name }}</span>
            @endif
        </div>
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
                @can('update', $category)
                    <button type="button" @click="open = false" wire:click="startEditCategoryName('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Rename Category</button>
                @endcan
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
    <tr
        x-show="{{ $summary['childrenVisibilityCondition'] }}"
        x-cloak
        wire:key="task-row-{{ $task->id }}"
        @contextmenu.prevent.stop="openContextMenu($event, {
            type: 'task',
            id: '{{ $task->id }}',
            canUpdate: @js(auth()->user()?->can('update', $task)),
            canDelete: @js(auth()->user()?->can('delete', $task)),
            canCreateTask: @js(auth()->user()?->can('create', \App\Domains\Tasks\Models\Task::class)),
        })"
    >
        <td class="px-3 py-2 align-top text-sm text-zinc-800 dark:text-zinc-200" @style(["padding-left: {$taskIndent}px"] )>
            @if ($editingTaskTitle === $task->id && auth()->user()?->can('update', $task))
                <form wire:submit="saveTaskTitle" class="flex items-center gap-1">
                    <input
                        type="text"
                        wire:model="editingTaskTitleValue"
                        wire:keydown.escape="cancelEditTaskTitle"
                        class="rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        x-init="$el.focus(); $el.select()"
                    />
                    <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">Save</button>
                    <button type="button" wire:click="cancelEditTaskTitle" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Cancel</button>
                </form>
            @else
                <span @if(auth()->user()?->can('update', $task)) @dblclick="$wire.startEditTaskTitle('{{ $task->id }}')" title="Double-click to rename" @endif>{{ $task->title }}</span>
            @endif
        </td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">Task</td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">
            @if ($editingTaskStatus === $task->id && auth()->user()?->can('updateStatus', $task))
                <select wire:model.live="editingTaskStatusValue" wire:change="saveTaskStatus" class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    @foreach (Task::statuses() as $status)
                        <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
            @else
                <span class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded px-1" @if(auth()->user()?->can('updateStatus', $task)) wire:click="startEditTaskStatus('{{ $task->id }}')" @endif>
                    {{ str($task->status)->replace('_', ' ')->headline() }}
                </span>
            @endif
        </td>
        <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">
            @if ($editingTaskPriority === $task->id && auth()->user()?->can('updatePriority', $task))
                <select wire:model.live="editingTaskPriorityValue" wire:change="saveTaskPriority" class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    @foreach (Task::priorities() as $priority)
                        <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            @else
                <span class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded px-1" @if(auth()->user()?->can('updatePriority', $task)) wire:click="startEditTaskPriority('{{ $task->id }}')" @endif>
                    {{ ucfirst($task->priority) }}
                </span>
            @endif
        </td>
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
                        <button type="button" @click="open = false" wire:click="startEditTaskTitle('{{ $task->id }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Rename Task</button>
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
        <tr
            x-show="{{ $summary['childrenVisibilityCondition'] }}"
            x-cloak
            wire:key="subtask-row-{{ $subTask->id }}"
            @contextmenu.prevent.stop="openContextMenu($event, {
                type: 'task',
                id: '{{ $subTask->id }}',
                canUpdate: @js(auth()->user()?->can('update', $subTask)),
                canDelete: @js(auth()->user()?->can('delete', $subTask)),
                canCreateTask: @js(auth()->user()?->can('create', \App\Domains\Tasks\Models\Task::class)),
            })"
        >
            <td class="px-3 py-2 align-top text-sm text-zinc-700 dark:text-zinc-300" @style(["padding-left: {$subTaskIndent}px"] )>
                @if ($editingTaskTitle === $subTask->id && auth()->user()?->can('update', $subTask))
                    <form wire:submit="saveTaskTitle" class="flex items-center gap-1">
                        <input
                            type="text"
                            wire:model="editingTaskTitleValue"
                            wire:keydown.escape="cancelEditTaskTitle"
                            class="rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            x-init="$el.focus(); $el.select()"
                        />
                        <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">Save</button>
                        <button type="button" wire:click="cancelEditTaskTitle" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Cancel</button>
                    </form>
                @else
                    <span @if(auth()->user()?->can('update', $subTask)) @dblclick="$wire.startEditTaskTitle('{{ $subTask->id }}')" title="Double-click to rename" @endif>-> {{ $subTask->title }}</span>
                @endif
            </td>
            <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">Subtask</td>
            <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ str($subTask->status)->replace('_', ' ')->headline() }}</td>
            <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ ucfirst($subTask->priority) }}</td>
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
                            <button type="button" @click="open = false" wire:click="startEditTaskTitle('{{ $subTask->id }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Rename Task</button>
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