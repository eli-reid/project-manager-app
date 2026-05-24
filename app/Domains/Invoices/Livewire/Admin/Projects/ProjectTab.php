<?php

namespace App\Domains\Invoices\Livewire\Admin\Projects;

use App\Domains\Projects\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    public Project $project;

    public Collection $invoices;

    public int $invoiceCount = 0;

    public function mount(Project $project, Collection $invoices, int $invoiceCount): void
    {
        $this->project = $project;
        $this->invoices = $invoices;
        $this->invoiceCount = $invoiceCount;
    }

    public function render()
    {
        return view('invoices::components.project-tab');
    }
}
