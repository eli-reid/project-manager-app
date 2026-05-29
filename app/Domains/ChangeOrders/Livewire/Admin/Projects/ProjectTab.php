<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\Projects;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * @var Collection<int, ChangeOrder>
     */
    public $changeOrders;

    public int $changeOrderCount = 0;

    public function mount(Project $project, $changeOrders = null, int $changeOrderCount = 0): void
    {
        $this->project = $project;
        $this->changeOrders = $changeOrders ?? collect();
        $this->changeOrderCount = $changeOrderCount;
    }

    public function render()
    {
        return view('change-orders::livewire.admin.projects.project-tab');
    }
}
