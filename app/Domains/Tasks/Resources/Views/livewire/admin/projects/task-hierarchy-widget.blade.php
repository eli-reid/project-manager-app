<div x-data="@include('tasks::livewire.admin.projects._task-hierarchy-root-state')" @click="closeContextMenu()" @keydown.escape.window="closeContextMenu()" @open-copy-category-modal.window="showCopyCategoryModal = true" @close-copy-category-modal.window="showCopyCategoryModal = false" @open-copy-task-modal.window="showCopyTaskModal = true" @open-save-template-modal.window="showSaveTemplateModal = true" @close-save-template-modal.window="showSaveTemplateModal = false" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Project Work Breakdown</h2>
        @include('tasks::livewire.admin.projects._task-hierarchy-actions-menu')
    </div>

    @include('tasks::livewire.admin.projects._task-hierarchy-copy-category-modal')

    @include('tasks::livewire.admin.projects._task-hierarchy-save-template-modal')

    @include('tasks::livewire.admin.projects._task-hierarchy-copy-task-modal')
    @include('tasks::livewire.admin.projects._task-hierarchy-inline-category-form')
    @include('tasks::livewire.admin.projects._task-hierarchy-inline-task-form')
    @include('tasks::livewire.admin.projects._task-hierarchy-copy-category-tasks-modal')

    <livewire:tasks.admin.projects.task-hierarchy-metrics
        :cards="$metricCards"
        :key="'task-hierarchy-metrics-'.$project->id.'-'.$taskCount.'-'.$inProgressTaskCount.'-'.$completedTaskCount.'-'.$overdueTaskCount"
    />

    @include('tasks::livewire.admin.projects._task-hierarchy-table-shell')
    @include('tasks::livewire.admin.projects._task-hierarchy-context-menu')

    @if ($canViewTaskTemplates)
        <livewire:tasks.admin.projects.task-hierarchy-templates
            :templates="$templateItems"
            :manage-url="$taskTemplateManageUrl"
            :key="'task-hierarchy-templates-'.$project->id.'-'.count($templateItems)"
        />
    @endif
</div>