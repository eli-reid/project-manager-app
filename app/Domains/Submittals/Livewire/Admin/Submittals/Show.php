<?php

namespace App\Domains\Submittals\Livewire\Admin\Submittals;

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Services\SubmittalLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.stock-invoices-admin')]
#[Title('Review Submittal')]
class Show extends Component
{
    use AuthorizesRequests;

    public Submittal $submittal;

    public string $comment = '';

    public string $rejectionReason = '';

    public function mount(Submittal $submittal): void
    {
        $this->authorize('view', $submittal);
        $this->submittal = $submittal->load([
            'project:id,name,project_number',
            'submittedBy:id,first_name,last_name,email',
            'currentReviewer:id,first_name,last_name,email',
            'items',
            'approvals.reviewer:id,first_name,last_name,email',
            'documents:id,title,original_name',
        ]);
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->submittal);

        $reviewer = Auth::user();
        abort_unless($reviewer instanceof User, 401);

        app(SubmittalLifecycleService::class)->approve($this->submittal, $reviewer, $this->comment);

        $this->comment = '';
        $this->reloadSubmittal();

        session()->flash('success', 'Review step approved.');
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->submittal);

        $reviewer = Auth::user();
        abort_unless($reviewer instanceof User, 401);

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3'],
        ]);

        app(SubmittalLifecycleService::class)->reject($this->submittal, $reviewer, $this->rejectionReason);

        $this->rejectionReason = '';
        $this->reloadSubmittal();

        session()->flash('success', 'Submittal rejected.');
    }

    public function distribute(): void
    {
        $this->authorize('distribute', $this->submittal);

        app(SubmittalLifecycleService::class)->distribute($this->submittal);

        $this->reloadSubmittal();

        session()->flash('success', 'Submittal distributed.');
    }

    public function cancel(): void
    {
        $this->authorize('cancel', $this->submittal);

        app(SubmittalLifecycleService::class)->cancel($this->submittal);

        $this->reloadSubmittal();

        session()->flash('success', 'Submittal cancelled.');
    }

    public function revise(): void
    {
        $this->authorize('revise', $this->submittal);

        app(SubmittalLifecycleService::class)->revise($this->submittal);

        $this->reloadSubmittal();

        session()->flash('success', 'Submittal marked for revision. The submitter can now edit and resubmit.');
    }

    private function reloadSubmittal(): void
    {
        $this->submittal->refresh()->load([
            'project:id,name,project_number',
            'submittedBy:id,first_name,last_name,email',
            'currentReviewer:id,first_name,last_name,email',
            'items',
            'approvals.reviewer:id,first_name,last_name,email',
            'documents:id,title,original_name',
        ]);
    }

    public function render()
    {
        return view('submittals::livewire.admin.submittals.show');
    }
}
