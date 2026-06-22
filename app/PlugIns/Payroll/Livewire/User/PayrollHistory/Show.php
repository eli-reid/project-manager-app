<?php

namespace App\Domains\Payroll\Livewire\User\PayrollHistory;

use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Services\PayStubPdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Pay Stub')]
class Show extends Component
{
    use AuthorizesRequests;

    public PayrollStatement $payrollStatement;

    public function mount(PayrollStatement $payrollStatement): void
    {
        $this->authorize('payroll-stubs.view-own');

        $userId = Auth::id();
        abort_unless(is_string($userId), 401);

        $canViewAll = Auth::user()?->can('payroll-stubs.view-all') ?? false;

        if (! $canViewAll && (string) $payrollStatement->user_id !== $userId) {
            abort(403);
        }

        $this->payrollStatement = $payrollStatement;
    }

    public function downloadPdf(PayStubPdfService $payStubPdfService): StreamedResponse
    {
        $payDate = optional($this->payrollStatement->payRun?->pay_date)->format('Ymd') ?? now()->format('Ymd');
        $filename = "pay-stub-{$payDate}.pdf";
        $pdf = $payStubPdfService->generate($this->payrollStatement->loadMissing(['user:id,first_name,last_name', 'payRun:id,pay_date,pay_period_start,pay_period_end']));

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function render(): View
    {
        return view('payroll::livewire.user.payroll-history.show', [
            'stub' => $this->payrollStatement->load([
                'user:id,first_name,last_name',
                'payRun:id,pay_date,pay_period_start,pay_period_end,status',
                'payrollEmployeeProfile:id,employee_number,user_id',
            ]),
        ]);
    }
}
