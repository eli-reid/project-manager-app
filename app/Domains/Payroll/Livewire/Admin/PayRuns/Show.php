<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRuns;

use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Services\PayRunService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('payroll::livewire.layouts.payroll-admin')]
#[Title('Payroll Pay Run Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public PayRun $payRun;

    public function mount(PayRun $payRun): void
    {
        $this->authorize('payroll-runs.preview');
        $this->payRun = $payRun;
    }

    public function approve(PayRunService $payRunService): void
    {
        $this->authorize('payroll-runs.approve');

        $userId = Auth::id();
        abort_unless(is_string($userId), 401);

        try {
            $payRunService->approve($this->payRun, $userId);
            session()->flash('success', 'Pay run approved.');
        } catch (DomainException $exception) {
            $this->addError('status', $exception->getMessage());
        }

        $this->refreshRun();
    }

    public function finalize(PayRunService $payRunService): void
    {
        $this->authorize('payroll-runs.finalize');

        try {
            $payRunService->finalize($this->payRun);
            session()->flash('success', 'Pay run finalized.');
        } catch (DomainException $exception) {
            $this->addError('status', $exception->getMessage());
        }

        $this->refreshRun();
    }

    public function voidRun(PayRunService $payRunService): void
    {
        $this->authorize('payroll-runs.void');

        try {
            $payRunService->voidRun($this->payRun);
            session()->flash('success', 'Pay run voided.');
        } catch (DomainException $exception) {
            $this->addError('status', $exception->getMessage());
        }

        $this->refreshRun();
    }

    public function render(): View
    {
        return view('payroll::livewire.admin.pay-runs.show', [
            'run' => $this->payRun->load([
                'creator:id,first_name,last_name',
                'approver:id,first_name,last_name',
                'payrollStatements.user:id,first_name,last_name',
                'payrollStatements.payrollEmployeeProfile:id,employee_number,user_id',
            ]),
        ]);
    }

    private function refreshRun(): void
    {
        $this->payRun = $this->payRun->fresh() ?? $this->payRun;
    }
}
