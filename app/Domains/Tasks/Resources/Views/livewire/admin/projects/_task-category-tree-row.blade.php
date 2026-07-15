@php
    $category = $categoryRow['category'];
    $categoryId = $categoryRow['categoryId'];
    $summary = $categoryRow['summary'];
    $categoryIndent = $categoryRow['categoryIndent'];
    $progressWidth = $categoryRow['progressWidth'];
@endphp

<tr
    @class([
        'bg-zinc-50/70 dark:bg-zinc-800/50',
        'ring-1 ring-zinc-300 dark:ring-zinc-600' => in_array($categoryId, $selectedCategoryIds, true),
    ])
    x-show="{{ $summary['ancestorVisibilityCondition'] }}"
    x-cloak
    wire:key="category-row-{{ $categoryId }}"
    @contextmenu.prevent.stop="openContextMenu($event, {
        type: 'category',
        id: '{{ $categoryId }}',
        canUpdate: @js($canUpdateTaskCategory),
        canDelete: @js($canDeleteTaskCategory),
        canCreateTask: @js($canCreateTask),
        canCreateTemplate: @js($canCreateTaskTemplate),
        canUpdateStatus: false,
    })"
>
    <td class="px-3 py-2 align-top text-sm text-zinc-800 dark:text-zinc-200">
        <input type="checkbox" wire:model.live="selectedCategoryIds" value="{{ $categoryId }}" @click.stop class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" aria-label="Select category {{ $category->name }}" />
    </td>
    <td class="px-3 py-2 align-top text-sm font-semibold text-zinc-900 dark:text-zinc-100" @style(["padding-left: {$categoryIndent}px"] )>
        <div class="inline-flex items-center gap-2">
            <button type="button" @click="toggleCategory('{{ $categoryId }}')" class="inline-flex items-center justify-center rounded-md p-1 hover:bg-zinc-200/70 dark:hover:bg-zinc-700/70" aria-label="Toggle category">
                <svg class="h-3.5 w-3.5 text-zinc-500 transition-transform" :class="isCollapsed('{{ $categoryId }}') ? '' : 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" />
                </svg>
            </button>
            @if ($editingCategoryName === $categoryId && $canUpdateTaskCategory)
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
                <span @if($canUpdateTaskCategory) @dblclick="$wire.startEditCategoryName('{{ $categoryId }}')" title="Double-click to rename" @endif class="cursor-default">{{ $category->name }}</span>
                <span class="ml-1 rounded-full bg-zinc-200 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">{{ $summary['taskCount'] }}</span>
            @endif
        </div>
    </td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">Category</td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">
        <div class="w-full max-w-40">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400" @style(["width: {$progressWidth}"] )></div>
            </div>
            <div class="mt-1">{{ $summary['progressPercent'] }}% complete ({{ $summary['completedTaskCount'] }}/{{ $summary['taskCount'] }})</div>
        </div>
    </td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">—</td>
    <td class="px-3 py-2 align-top text-xs text-zinc-500 dark:text-zinc-400">—</td>
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
                @if ($canUpdateTaskCategory)
                    <button type="button" @click="open = false" wire:click="startEditCategoryName('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Rename Category</button>
                @endif
                <button type="button" @click="open = false" wire:click="copyCategoryFrom('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category</button>
                <button type="button" @click="open = false; showCopyModal = true" wire:click="$set('copySourceCategoryId', '{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category Tasks</button>
                @if ($canCreateTaskTemplate)
                    <button type="button" @click="open = false" wire:click="startSaveCategoryAsTemplate('{{ $categoryId }}')" class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Save as Template</button>
                @endif
                @if ($canDeleteTaskCategory)
                    <button
                        type="button"
                        @click="open = false"
                        wire:click="deleteCategory('{{ $categoryId }}')"
                        wire:confirm="Delete this category branch? This deletes the category, all subcategories, and all tasks in that branch."
                        class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
                    >
                        Delete Category
                    </button>
                @endif
            </div>
        </div>
    </td>
</tr>