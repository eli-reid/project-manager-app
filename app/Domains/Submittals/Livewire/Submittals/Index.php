<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Domains\Submittals\Models\Submittal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Submittals')]
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

        return view('submittals::livewire.user.submittals.index', [
            'submittals' => $query->paginate(15),
            'statuses' => [
                Submittal::STATUS_DRAFT => 'Draft',
                Submittal::STATUS_UNDER_REVIEW => 'Under Review',
                Submittal::STATUS_ARCHITECT_REVIEW => 'Architect Review',
                Submittal::STATUS_OWNER_REVIEW => 'Owner Review',
                Submittal::STATUS_APPROVED => 'Approved',
                Submittal::STATUS_REJECTED => 'Rejected',
                Submittal::STATUS_REVISE => 'Revise',
                Submittal::STATUS_DISTRIBUTED => 'Distributed',
                Submittal::STATUS_CANCELLED => 'Cancelled',
            ],
        ]);
    }
}
