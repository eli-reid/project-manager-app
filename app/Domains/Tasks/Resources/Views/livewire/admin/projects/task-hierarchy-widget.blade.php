<div x-data="{
    showCopyModal: false,
    showCopyCategoryModal: false,
    contextMenuOpen: false,
    contextMenuX: 0,
    contextMenuY: 0,
    contextMenuType: null,
    contextMenuId: null,
    contextMenuCanUpdate: false,
    contextMenuCanDelete: false,
    contextMenuCanCreateTask: false,
    openContextMenu(event, payload) {
        this.contextMenuOpen = true;
        this.contextMenuX = event.clientX;
        this.contextMenuY = event.clientY;
        this.contextMenuType = payload.type;
        this.contextMenuId = payload.id;
        this.contextMenuCanUpdate = !!payload.canUpdate;
        this.contextMenuCanDelete = !!payload.canDelete;
        this.contextMenuCanCreateTask = !!payload.canCreateTask;
    },
    closeContextMenu() {
        this.contextMenuOpen = false;
        this.contextMenuType = null;
        this.contextMenuId = null;
    },
    buildMenuState(menuHeight, offset = 4) {
        return {
            open: false,
            menuStyle: '',
            toggleMenu(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const right = window.innerWidth - rect.right;

                if (spaceBelow < menuHeight) {
                    this.menuStyle = 'bottom: ' + (window.innerHeight - rect.top + offset) + 'px; right: ' + right + 'px;';
                } else {
                    this.menuStyle = 'top: ' + (rect.bottom + offset) + 'px; right: ' + right + 'px;';

                this.open = !this.open;
            },
            closeMenu() {
                this.open = false;
            },
        };
    },
}" @click="closeContextMenu()" @keydown.escape.window="closeContextMenu()" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Project Work Breakdown</h2>
        <div
            class="relative"
            x-data="buildMenuState(220, 6)"
            @click.away="closeMenu()"
        >
            <button type="button" @click="toggleMenu($event)" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" aria-label="Task actions">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <circle cx="4" cy="10" r="1.5" />
                    <circle cx="10" cy="10" r="1.5" />
                    <circle cx="16" cy="10" r="1.5" />
                </svg>
            </button>

            <div x-show="open" x-cloak class="fixed z-30 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :style="menuStyle">
                @can('create', \App\Domains\Tasks\Models\Task::class)
                    <button type="button" @click="closeMenu()" wire:click="startInlineTaskForm" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Task</button>
                @endcan
                @can('create', \App\Domains\Tasks\Models\TaskCategory::class)
                    <button type="button" @click="closeMenu()" wire:click="startInlineCategoryForm" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Category</button>
                @endcan
                @can('create', \App\Domains\Tasks\Models\Task::class)
                    <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                    <a href="{{ route('admin.tasks.create', ['project_id' => $project->id]) }}" wire:navigate class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Add Task</a>
                @endcan
                @can('create', \App\Domains\Tasks\Models\TaskCategory::class)
                    <a href="{{ route('admin.task-categories.create', ['project_id' => $project->id]) }}" wire:navigate class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Add Category</a>
                @endcan
                @can('create', \App\Domains\Tasks\Models\TaskCategory::class)
                    <button type="button" @click="closeMenu(); showCopyCategoryModal = true" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category</button>
                @endcan
                @can('create', \App\Domains\Tasks\Models\Task::class)
                    <button type="button" @click="closeMenu(); showCopyModal = true" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category Tasks</button>
                @endcan
            </div>
        </div>
    </div>

    <div x-show="showCopyCategoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4" @click.self="showCopyCategoryModal = false">
        <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Copy Category</h3>
                <button type="button" @click="showCopyCategoryModal = false" class="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close">x</button>
            </div>

            <form wire:submit="copyCategory" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Source Category</label>
                    <select wire:model="copyCategorySourceId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Select a source category</option>
                        @foreach ($copyCategoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('copyCategorySourceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <button type="button" @click="showCopyCategoryModal = false" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                    <button type="submit" @click="showCopyCategoryModal = false" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Copy Category</button>
                </div>
            </form>
        </div>
    </div>

    @if ($showInlineCategoryForm)
        <form wire:submit="createInlineCategory" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Quick Add Category</p>
            <div class="grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</label>
                    <input type="text" wire:model="inlineCategoryName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('inlineCategoryName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Parent</label>
                    <select wire:model="inlineCategoryParentId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Top level</option>
                        @foreach ($copyCategoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('inlineCategoryParentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                    <textarea wire:model="inlineCategoryDescription" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                    @error('inlineCategoryDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="cancelInlineCategoryForm" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Category</button>
            </div>
        </form>
    @endif

    @if ($showInlineTaskForm)
        <form wire:submit="createInlineTask" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Quick Add Task</p>
            <div class="grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</label>
                    <input type="text" wire:model="inlineTaskTitle" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('inlineTaskTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Category</label>
                    <select wire:model="inlineTaskCategoryId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Select category</option>
                        @foreach ($copyCategoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('inlineTaskCategoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                    <textarea wire:model="inlineTaskDescription" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                    @error('inlineTaskDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Assigned To</label>
                    <select wire:model="inlineTaskAssignedTo" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Unassigned</option>
                        @foreach ($assignableUsers as $user)
                            <option value="{{ $user->id }}">{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) }}</option>
                        @endforeach
                    </select>
                    @error('inlineTaskAssignedTo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="cancelInlineTaskForm" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Task</button>
            </div>
        </form>
    @endif

    <div x-show="showCopyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4" @click.self="showCopyModal = false">
        <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Copy Category Tasks</h3>
                <button type="button" @click="showCopyModal = false" class="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="Close">x</button>
            </div>

            <form wire:submit="copyCategoryTasks" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Source Category</label>
                    <select wire:model="copySourceCategoryId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Select a source category</option>
                        @foreach ($copyCategoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('copySourceCategoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Target Category</label>
                    <select wire:model="copyTargetCategoryId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Uncategorized</option>
                        @foreach ($copyCategoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('copyTargetCategoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="copyIncludeSubtasks" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" />
                    <span>Include subtasks</span>
                </label>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <button type="button" @click="showCopyModal = false" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                    <button type="submit" @click="showCopyModal = false" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Copy Tasks</button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</p>
            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $taskCount }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">In Progress</p>
            <p class="mt-1 text-lg font-semibold text-amber-600 dark:text-amber-400">{{ $inProgressTaskCount }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Completed</p>
            <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">{{ $completedTaskCount }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Overdue</p>
            <p class="mt-1 text-lg font-semibold text-rose-600 dark:text-rose-400">{{ $overdueTaskCount }}</p>
        </div>
    </div>

    <div class="overflow-x-auto" x-data="{ collapsedCategories: @js($collapsedCategoryIds), isCollapsed(id) { return this.collapsedCategories.includes(id); }, toggleCategory(id) { this.collapsedCategories = this.isCollapsed(id) ? this.collapsedCategories.filter(item => item !== id) : [...this.collapsedCategories, id]; } }">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Priority</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Assigned</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @if ($hasTaskHierarchy)
                    @foreach ($categories as $category)
                        @include('tasks::livewire.admin.projects._task-category-tree-row', [
                            'category' => $category,
                            'depth' => 0,
                            'tasksByCategory' => $tasksByCategory,
                            'categorySummaries' => $categorySummaries,
                        ])
                    @endforeach

                    @foreach ($uncategorizedTasks as $task)
                        <tr
                            wire:key="uncategorized-task-row-{{ $task->id }}"
                            @contextmenu.prevent.stop="openContextMenu($event, {
                                type: 'task',
                                id: '{{ $task->id }}',
                                canUpdate: @js(auth()->user()?->can('update', $task)),
                                canDelete: @js(auth()->user()?->can('delete', $task)),
                                canCreateTask: @js(auth()->user()?->can('create', \App\Domains\Tasks\Models\Task::class)),
                            })"
                        >
                            <td class="px-3 py-2 align-top text-sm text-zinc-800 dark:text-zinc-200">
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
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No tasks found for this project.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div
        x-show="contextMenuOpen"
        x-cloak
        @click.stop
        class="fixed z-40 w-48 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        :style="'top: ' + contextMenuY + 'px; left: ' + contextMenuX + 'px;'"
    >
        <template x-if="contextMenuType === 'category'">
            <div class="py-1">
                <button
                    type="button"
                    x-show="contextMenuCanCreateTask"
                    @click="closeContextMenu(); $wire.startInlineTaskForm(contextMenuId)"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Quick Add Task
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.startEditCategoryName(contextMenuId)"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Rename Category
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.moveCategory(contextMenuId, 'up')"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Move Up
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.moveCategory(contextMenuId, 'down')"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Move Down
                </button>
                <button
                    type="button"
                    @click="closeContextMenu(); $wire.copyCategoryFrom(contextMenuId)"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Copy Category
                </button>
                <button
                    type="button"
                    @click="closeContextMenu(); $wire.$set('copySourceCategoryId', contextMenuId); showCopyModal = true"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Copy Category Tasks
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanDelete"
                    @click="if (confirm('Delete this category branch? This deletes the category, all subcategories, and all tasks in that branch.')) { closeContextMenu(); $wire.deleteCategory(contextMenuId); }"
                    class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
                >
                    Delete Category
                </button>
            </div>
        </template>

        <template x-if="contextMenuType === 'task'">
            <div class="py-1">
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.startEditTaskTitle(contextMenuId)"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Rename Task
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.moveTask(contextMenuId, 'up')"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Move Up
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanUpdate"
                    @click="closeContextMenu(); $wire.moveTask(contextMenuId, 'down')"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Move Down
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanCreateTask"
                    @click="closeContextMenu(); $wire.copyTaskFrom(contextMenuId)"
                    class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Copy Task
                </button>
                <button
                    type="button"
                    x-show="contextMenuCanDelete"
                    @click="if (confirm('Delete this task?')) { closeContextMenu(); $wire.deleteTask(contextMenuId); }"
                    class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
                >
                    Delete Task
                </button>
            </div>
        </template>
    </div>

    @if ($canViewTaskTemplates)
        <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Task Templates</h3>
                @can('viewAny', \App\Domains\Tasks\Models\TaskTemplate::class)
                    <a href="{{ route('admin.task-templates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Manage Templates</a>
                @endcan
            </div>

            <ul class="space-y-2">
                @forelse ($templates as $template)
                    <li class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $template->name }}</span>
                        <span class="text-zinc-500">({{ ucfirst($template->priority) }})</span>
                    </li>
                @empty
                    <li class="rounded-lg border border-zinc-200 bg-white px-3 py-4 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">No templates available.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>