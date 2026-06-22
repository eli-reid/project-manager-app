<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRuns;

use App\Domains\Payroll\Services\PayRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('payroll::livewire.layouts.payroll-admin')]
#[Title('Create Payroll Pay Run')]
class Form extends Component
{
    use AuthorizesRequests;

    public string $pay_period_start = '';

    public string $pay_period_end = '';

    public string $pay_date = '';

    public function mount(): void
    {
        $this->authorize('payroll-runs.preview');

        $periodStart = now()->startOfWeek()->subDay();
        $periodEnd = $periodStart->copy()->addDays(6);
        $payDate = $periodEnd->copy()->addWeek();

        $this->pay_period_start = $periodStart->toDateString();
        $this->pay_period_end = $periodEnd->toDateString();
        $this->pay_date = $payDate->toDateString();
    }

    protected function rules(): array
    {
        return [
            'pay_period_start' => ['required', 'date'],
            'pay_period_end' => ['required', 'date', 'after_or_equal:pay_period_start'],
            'pay_date' => ['required', 'date', 'after_or_equal:pay_period_end'],
        ];
    }

    public function createPreview(PayRunService $payRunService): void
    {
        $validated = $this->validate();
        $userId = Auth::id();
        abort_unless(is_string($userId), 401);

        $run = $payRunService->createPreview(
            payPeriodStart: $validated['pay_period_start'],
            payPeriodEnd: $validated['pay_period_end'],
            payDate: $validated['pay_date'],
            createdBy: $userId,
        );

        session()->flash('success', 'Preview pay run created successfully.');

        $this->redirectRoute('admin.payroll.runs.show', ['payRun' => $run], navigate: true);
    }

    public function render(): View
    {
        return view('payroll::livewire.admin.pay-runs.form');
    }
}
