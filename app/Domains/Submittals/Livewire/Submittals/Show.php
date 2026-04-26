<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Services\SubmittalLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Submittal Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Submittal $submittal;

    public function mount(Submittal $submittal): void
    {
        $this->authorize('view', $submittal);
        $this->submittal = $submittal->load([
            'project:id,name,project_number',
            'submittedBy:id,first_name,last_name,email',
            'currentReviewer:id,first_name,last_name,email',
            'items',
            'approvals.reviewer:id,first_name,last_name,email',
        ]);
    }

    public function submit(): void
    {
        $this->authorize('submit', $this->submittal);

        app(SubmittalLifecycleService::class)->submit($this->submittal);

        $this->submittal->refresh()->load([
            'project:id,name,project_number',
            'submittedBy:id,first_name,last_name,email',
            'currentReviewer:id,first_name,last_name,email',
            'items',
            'approvals.reviewer:id,first_name,last_name,email',
        ]);

        session()->flash('success', 'Submittal submitted for review.');
    }

    public function render()
    {
        return view('submittals::livewire.user.submittals.show');
    }
}
