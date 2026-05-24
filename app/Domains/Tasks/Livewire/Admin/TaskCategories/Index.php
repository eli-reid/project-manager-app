<?php

namespace App\Domains\Tasks\Livewire\Admin\TaskCategories;

use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Services\TaskTreeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Task Categories')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', TaskCategory::class);
    }

    public function deleteCategory(string $taskCategoryId): void
    {
        $taskCategory = TaskCategory::query()->withCount(['children', 'tasks'])->findOrFail($taskCategoryId);
        $this->authorize('delete', $taskCategory);

        if ($taskCategory->children_count > 0 || $taskCategory->tasks_count > 0) {
            session()->flash('error', 'Category cannot be deleted while it has child categories or tasks.');

            return;
        }

        $projectId = $taskCategory->project_id;
        $taskCategory->delete();

        app(TaskTreeService::class)->clearCategoryTreeCache($projectId);
        session()->flash('success', 'Task category deleted successfully.');
    }

    public function render()
    {
        return view('tasks::livewire.admin.task-categories.index', [
            'rootCategories' => app(TaskTreeService::class)->getCachedCategoryTree(),
        ]);
    }
}
