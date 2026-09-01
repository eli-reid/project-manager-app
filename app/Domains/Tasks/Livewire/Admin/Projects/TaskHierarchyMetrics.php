<?php

namespace App\Domains\Tasks\Livewire\Admin\Projects;

use Illuminate\View\View;
use Livewire\Component;

class TaskHierarchyMetrics extends Component
{
    /**
     * @var array<int, array{label:string,value:string,valueClass:string}>
     */
    public array $cards = [];

    /**
     * @param  array<int, array{label:string,value:string,valueClass:string}>  $cards
     */
    public function mount(array $cards = []): void
    {
        $this->cards = $cards;
    }

    public function render(): View
    {
        return view('tasks::livewire.admin.projects.task-hierarchy-metrics');
    }
}
