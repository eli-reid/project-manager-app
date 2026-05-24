<?php

namespace App\Domains\Tasks\Livewire\Admin\TaskTemplates;

use App\Domains\Tasks\Models\TaskTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Task Templates')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', TaskTemplate::class);
    }

    public function deleteTemplate(string $templateId): void
    {
        $template = TaskTemplate::query()->findOrFail($templateId);
        $this->authorize('delete', $template);

        $template->delete();

        session()->flash('success', 'Task template deleted successfully.');
    }

    public function render()
    {
        return view('tasks::livewire.admin.task-templates.index', [
            'templates' => TaskTemplate::query()
                ->with(['category:id,name', 'creator:id,first_name,last_name'])
                ->latest()
                ->paginate(10),
        ]);
    }
}
