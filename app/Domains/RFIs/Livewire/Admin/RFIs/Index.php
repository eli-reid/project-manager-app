<?php

namespace App\Domains\RFIs\Livewire\Admin\RFIs;

use App\Domains\RFIs\Models\RFI;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('RFIs')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RFI::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = RFI::query()
            ->with(['project:id,name,project_number', 'requestedBy:id,first_name,last_name'])
            ->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('rfis::livewire.admin.rfis.index', [
            'rfis' => $query->paginate(15),
            'statuses' => RFI::statuses(),
        ]);
    }
}
