<?php

namespace App\Domains\RFIs\Livewire\Admin\RFIs;

use App\Core\Identity\Models\User;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Review RFI')]
class Show extends Component
{
    use AuthorizesRequests;

    public RFI $rfi;

    public string $answer = '';

    public string $costImpact = '';

    public string $scheduleImpactDays = '';

    public function mount(RFI $rfi): void
    {
        $this->authorize('view', $rfi);
        $this->rfi = $rfi->load(['project:id,name,project_number', 'requestedBy', 'answeredBy', 'documents']);
    }

    public function answer(RFILifecycleService $service): void
    {
        $this->authorize('answer', $this->rfi);

        $this->validate([
            'answer' => ['required', 'string', 'min:5'],
            'costImpact' => ['nullable', 'numeric', 'min:0'],
            'scheduleImpactDays' => ['nullable', 'integer'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $service->answer($this->rfi, $user, [
            'answer' => $this->answer,
            'cost_impact' => $this->costImpact !== '' ? $this->costImpact : null,
            'schedule_impact_days' => $this->scheduleImpactDays !== '' ? (int) $this->scheduleImpactDays : null,
        ]);

        $this->rfi->refresh();
        $this->answer = '';
        $this->costImpact = '';
        $this->scheduleImpactDays = '';

        $this->dispatch('notify', message: 'RFI answered successfully.');
    }

    public function close(RFILifecycleService $service): void
    {
        $this->authorize('close', $this->rfi);

        $service->close($this->rfi);

        $this->rfi->refresh();
    }

    public function cancel(RFILifecycleService $service): void
    {
        $this->authorize('cancel', $this->rfi);

        $service->cancel($this->rfi);

        $this->rfi->refresh();
    }

    public function render()
    {
        return view('rfis::livewire.admin.rfis.show');
    }
}
