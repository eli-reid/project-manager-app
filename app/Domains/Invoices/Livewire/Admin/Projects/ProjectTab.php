<?php

namespace App\Domains\Invoices\Livewire\Admin\Projects;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectTab extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public Collection $invoices;

    public int $invoiceCount = 0;

    public function mount(Project $project, Collection $invoices, int $invoiceCount): void
    {
        $this->project = $project;
        $this->invoices = $invoices;
        $this->invoiceCount = $invoiceCount;
    }

    public function deleteInvoice(string $invoiceId): void
    {
        $invoice = Invoice::query()
            ->where('project_id', $this->project->id)
            ->findOrFail($invoiceId);

        $this->authorize('delete', $invoice);

        $invoice->delete();
        $this->invoices = $this->invoices
            ->reject(fn ($projectInvoice): bool => $projectInvoice->is($invoice))
            ->values();
        $this->invoiceCount--;

        session()->flash('success', 'Invoice deleted successfully.');
    }

    public function render()
    {
        return view('invoices::components.project-tab');
    }
}
