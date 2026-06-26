<?php

namespace App\Domains\Payroll\Livewire\User\PayrollHistory;

use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Payroll History')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('payroll-stubs.view-own');
    }

    public function render(): View
    {
        $userId = Auth::id();
        abort_unless(is_string($userId), 401);

        $payDateSubquery = PayRun::query()
            ->select('pay_date')
            ->whereColumn('pay_runs.id', 'payroll_statements.pay_run_id')
            ->limit(1);

        return view('payroll::livewire.user.payroll-history.index', [
            'stubs' => PayrollStatement::query()
                ->with([
                    'payRun:id,pay_date,pay_period_start,pay_period_end,status',
                    'payrollEmployeeProfile:id,employee_number,user_id',
                ])
                ->where('user_id', $userId)
                ->orderByDesc($payDateSubquery)
                ->paginate(12),
        ]);
    }
}
