<?php

namespace App\Domains\PaymentReceipts\Livewire\Admin\Projects;

use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\PaymentReceipts\Services\PaymentReceiptLedgerService;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PaymentReceiptTab extends Component
{
    public Project $project;

    public string $receivedOn = '';

    public string $amount = '';

    public string $receivedFrom = '';

    public string $reference = '';

    public string $notes = '';

    private PaymentReceiptLedgerService $ledgerService;

    public function boot(PaymentReceiptLedgerService $ledgerService): void
    {
        $this->ledgerService = $ledgerService;
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', PaymentReceipt::class);

        $this->receivedOn = now()->toDateString();
    }

    public function recordPaymentReceipt(): void
    {
        Gate::authorize('create', PaymentReceipt::class);

        $validated = $this->validate([
            'receivedOn' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'receivedFrom' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        abort_unless($user !== null, 401);

        $this->ledgerService->record($this->project, $user, [
            'received_on' => $validated['receivedOn'],
            'amount' => $validated['amount'],
            'received_from' => $validated['receivedFrom'],
            'reference' => $validated['reference'],
            'notes' => $validated['notes'],
        ]);

        $this->amount = '';
        $this->receivedFrom = '';
        $this->reference = '';
        $this->notes = '';

        session()->flash('success', 'Payment receipt recorded successfully.');
    }

    public function deletePaymentReceipt(string $paymentReceiptId): void
    {
        $paymentReceipt = $this->project->paymentReceipts()
            ->whereKey($paymentReceiptId)
            ->firstOrFail();

        Gate::authorize('delete', $paymentReceipt);

        $this->ledgerService->delete($this->project, $paymentReceipt);

        session()->flash('success', 'Payment receipt deleted successfully.');
    }

    public function render()
    {
        return view('payment-receipts::livewire.admin.projects.payment-receipt-tab', [
            'paymentReceipts' => $this->project->paymentReceipts()
                ->with('creator')
                ->orderByDesc('received_on')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
