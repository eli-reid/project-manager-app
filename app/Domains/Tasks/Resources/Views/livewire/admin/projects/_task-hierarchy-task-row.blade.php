@php
    /** @var \App\Domains\Tasks\Models\Task $task */
    $task = $taskRow['task'];
    $taskId = $taskRow['taskId'];
    $visibilityCondition = $taskRow['visibilityCondition'] ?? null;
    $indent = $taskRow['indent'] ?? 0;
    $keyPrefix = $taskRow['keyPrefix'] ?? 'task-row';
@endphp

<tr
    @if (is_string($visibilityCondition) && $visibilityCondition !== '')
        x-show="{{ $visibilityCondition }}"
        x-cloak
    @endif
    wire:key="{{ $keyPrefix }}-{{ $taskId }}"
    @contextmenu.prevent.stop="openContextMenu($event, {
        type: 'task',
        id: '{{ $taskId }}',
        canUpdate: @js($canUpdateTask),
        canDelete: @js($canDeleteTask),
        canCreateTask: @js($canCreateTask),
    })"
>
    <td class="px-3 py-2 align-top text-sm text-zinc-800 dark:text-zinc-200" @style(["padding-left: {$indent}px"] )>
        @if ($editingTaskTitle === $taskId && $canUpdateTask)
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
            <span @if($canUpdateTask) @dblclick="$wire.startEditTaskTitle('{{ $taskId }}')" title="Double-click to rename" @endif>{{ $taskRow['displayTitle'] }}</span>
        @endif
    </td>
    <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ $taskRow['typeLabel'] }}</td>
    <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">
        @if (($taskRow['supportsInlineStatusEditing'] ?? false) && $editingTaskStatus === $taskId && $canUpdateTaskStatus)
            <select wire:model.live="editingTaskStatusValue" wire:change="saveTaskStatus" class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                @foreach (Task::statuses() as $status)
                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
        @else
            <span class="rounded px-1 {{ ($taskRow['supportsInlineStatusEditing'] ?? false) && $canUpdateTaskStatus ? 'cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800' : '' }}" @if(($taskRow['supportsInlineStatusEditing'] ?? false) && $canUpdateTaskStatus) wire:click="startEditTaskStatus('{{ $taskId }}')" @endif>
                {{ $taskRow['statusLabel'] }}
            </span>
        @endif
    </td>
    <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">
        @if (($taskRow['supportsInlinePriorityEditing'] ?? false) && $editingTaskPriority === $taskId && $canUpdateTaskPriority)
            <select wire:model.live="editingTaskPriorityValue" wire:change="saveTaskPriority" class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                @foreach (Task::priorities() as $priority)
                    <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
        @else
            <span class="rounded px-1 {{ ($taskRow['supportsInlinePriorityEditing'] ?? false) && $canUpdateTaskPriority ? 'cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800' : '' }}" @if(($taskRow['supportsInlinePriorityEditing'] ?? false) && $canUpdateTaskPriority) wire:click="startEditTaskPriority('{{ $taskId }}')" @endif>
                {{ $taskRow['priorityLabel'] }}
            </span>
        @endif
    </td>
    <td class="px-3 py-2 align-top text-sm text-zinc-600 dark:text-zinc-300">{{ $taskRow['assignedLabel'] }}</td>
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
                @if ($canUpdateTask)
                    <a href="{{ route('admin.tasks.edit', $task) }}" wire:navigate class="block px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit Task</a>
                    <button type="button" @click="open = false" wire:click="startEditTaskTitle('{{ $taskId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Rename Task</button>
                @endif
                @if ($canCreateTask)
                    <button type="button" @click="open = false" wire:click="copyTaskFrom('{{ $taskId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Task</button>
                @endif
                @if ($canDeleteTask)
                    <button type="button" @click="open = false" wire:click="deleteTask('{{ $taskId }}')" wire:confirm="Delete this task?" class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30">Delete Task</button>
                @endif
            </div>
        </div>
    </td>
</tr>

@foreach ($taskRow['subTaskRows'] ?? [] as $subTaskRow)
    @include('tasks::livewire.admin.projects._task-hierarchy-task-row', ['taskRow' => $subTaskRow])
@endforeach
