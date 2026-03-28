<?php

namespace App\Domains\Stock\Livewire\Admin\Templates;

use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Stock Order Templates')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', StockOrderTemplate::class);
    }

    public function deleteTemplate(string $templateId): void
    {
        $template = StockOrderTemplate::query()->findOrFail($templateId);
        $this->authorize('delete', $template);

        $template->delete();

        session()->flash('success', 'Template deleted successfully.');
    }

    public function toggleActive(string $templateId): void
    {
        $template = StockOrderTemplate::query()->findOrFail($templateId);
        $this->authorize('update', $template);

        $template->update(['is_active' => ! $template->is_active]);

        session()->flash('success', $template->is_active ? 'Template activated.' : 'Template deactivated.');
    }

    public function render()
    {
        return view('stock::livewire.admin.templates.index', [
            'templates' => StockOrderTemplate::query()
                ->with(['createdBy:id,first_name,last_name'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
