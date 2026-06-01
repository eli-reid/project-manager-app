<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\ChangeOrders\Services\ChangeOrderLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public ChangeOrder $changeOrder;

    public string $rejectionReason = '';

    /**
     * Mount the component.
     */
    public function mount(ChangeOrder $changeOrder): void
    {
        $this->authorize('view', $changeOrder);
        $this->changeOrder = $changeOrder->load([
            'project:id,name,project_number',
            'requestedBy:id,first_name,last_name',
            'approvedBy:id,first_name,last_name',
            'rejectedBy:id,first_name,last_name',
            'documents',
        ]);
    }

    public function submit(ChangeOrderLifecycleService $service): void
    {
        $this->authorize('submit', $this->changeOrder);

        $service->submit($this->changeOrder);

        $this->changeOrder->refresh()->loadMissing(['project:id,name,project_number', 'requestedBy:id,first_name,last_name', 'approvedBy:id,first_name,last_name', 'rejectedBy:id,first_name,last_name', 'documents']);
    }

    public function approve(ChangeOrderLifecycleService $service): void
    {
        $this->authorize('approve', $this->changeOrder);

        /** @var User $user */
        $user = Auth::user();

        $service->approve($this->changeOrder, $user);

        $this->changeOrder->refresh()->loadMissing(['project:id,name,project_number', 'requestedBy:id,first_name,last_name', 'approvedBy:id,first_name,last_name', 'rejectedBy:id,first_name,last_name', 'documents']);
    }

    public function reject(ChangeOrderLifecycleService $service): void
    {
        $this->authorize('reject', $this->changeOrder);

        $this->validate([
            'rejectionReason' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $service->reject($this->changeOrder, $user, $this->rejectionReason !== '' ? $this->rejectionReason : null);

        $this->rejectionReason = '';
        $this->changeOrder->refresh()->loadMissing(['project:id,name,project_number', 'requestedBy:id,first_name,last_name', 'approvedBy:id,first_name,last_name', 'rejectedBy:id,first_name,last_name', 'documents']);
    }

    public function implement(ChangeOrderLifecycleService $service): void
    {
        $this->authorize('implement', $this->changeOrder);

        $service->implement($this->changeOrder);

        $this->changeOrder->refresh()->loadMissing(['project:id,name,project_number', 'requestedBy:id,first_name,last_name', 'approvedBy:id,first_name,last_name', 'rejectedBy:id,first_name,last_name', 'documents']);
    }

    public function cancel(ChangeOrderLifecycleService $service): void
    {
        $this->authorize('cancel', $this->changeOrder);

        $service->cancel($this->changeOrder);

        $this->changeOrder->refresh()->loadMissing(['project:id,name,project_number', 'requestedBy:id,first_name,last_name', 'approvedBy:id,first_name,last_name', 'rejectedBy:id,first_name,last_name', 'documents']);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('change-orders::livewire.admin.change-orders.show');
    }
}
