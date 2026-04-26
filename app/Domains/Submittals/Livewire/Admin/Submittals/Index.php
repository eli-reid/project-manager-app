<?php

namespace App\Domains\Submittals\Livewire\Admin\Submittals;

use App\Domains\Submittals\Models\Submittal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.stock-invoices-admin')]
#[Title('Submittal Approval Queue')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Submittal::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Submittal::query()
            ->with(['project:id,name,project_number', 'submittedBy:id,first_name,last_name'])
            ->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('submittals::livewire.admin.submittals.index', [
            'submittals' => $query->paginate(15),
        ]);
    }
}
