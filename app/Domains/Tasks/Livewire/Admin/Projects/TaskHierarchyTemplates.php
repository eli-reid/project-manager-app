<?php

namespace App\Domains\Tasks\Livewire\Admin\Projects;

use Illuminate\View\View;
use Livewire\Component;

class TaskHierarchyTemplates extends Component
{
    /**
     * @var array<int, array{id:string,name:string,priorityLabel:string}>
     */
    public array $templates = [];

    public string $manageUrl = '';

    /**
     * @param  array<int, array{id:string,name:string,priorityLabel:string}>  $templates
     */
    public function mount(array $templates = [], string $manageUrl = ''): void
    {
        $this->templates = $templates;
        $this->manageUrl = $manageUrl;
    }

    public function render(): View
    {
        return view('tasks::livewire.admin.projects.task-hierarchy-templates');
    }
}
