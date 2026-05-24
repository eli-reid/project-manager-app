<?php

namespace App\Domains\Stock\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    public Project $project;

    public Collection $stockOrders;

    public int $stockOrderCount = 0;

    public function mount(Project $project, Collection $stockOrders, int $stockOrderCount): void
    {
        $this->project = $project;
        $this->stockOrders = $stockOrders;
        $this->stockOrderCount = $stockOrderCount;
    }

    public function render()
    {
        return view('stock::components.project-tab');
    }
}
