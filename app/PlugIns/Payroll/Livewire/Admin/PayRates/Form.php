<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRates;

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Projects\Models\Project;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('payroll::livewire.layouts.payroll-admin')]
#[Title('Payroll Rate Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?PayRate $payRate = null;

    public bool $isEdit = false;

    public string $payroll_employee_profile_id = '';

    public string $pay_rate_type_id = '';

    public string $project_id = '';

    public string $rate_amount = '';

    public string $effective_date = '';

    public string $expiration_date = '';

    public function mount(?PayRate $payRate = null): void
    {
        $this->authorize('payroll-rates.manage');

        if ($payRate !== null && $payRate->exists) {
            $this->payRate = $payRate;
            $this->isEdit = true;
            $this->payroll_employee_profile_id = (string) $payRate->payroll_employee_profile_id;
            $this->pay_rate_type_id = (string) $payRate->pay_rate_type_id;
            $this->project_id = (string) ($payRate->project_id ?? '');
            $this->rate_amount = number_format((float) $payRate->rate_amount, 4, '.', '');
            $this->effective_date = (string) optional($payRate->effective_date)->toDateString();
            $this->expiration_date = (string) optional($payRate->expiration_date)->toDateString();

            return;
        }

        $this->effective_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'payroll_employee_profile_id' => ['required', 'exists:payroll_employee_profiles,id'],
            'pay_rate_type_id' => ['required', 'exists:pay_rate_types,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        $approverId = Auth::id();
        abort_unless(is_string($approverId), 401);

        $payload = [
            'payroll_employee_profile_id' => $validated['payroll_employee_profile_id'],
            'pay_rate_type_id' => $validated['pay_rate_type_id'],
            'project_id' => $validated['project_id'] !== '' ? $validated['project_id'] : null,
            'rate_amount' => $validated['rate_amount'],
            'effective_date' => $validated['effective_date'],
            'expiration_date' => ($validated['expiration_date'] ?? '') !== '' ? $validated['expiration_date'] : null,
            'approved_by' => $approverId,
        ];

        try {
            if ($this->isEdit && $this->payRate !== null) {
                $this->payRate->update($payload);
                session()->flash('success', 'Pay rate updated successfully.');
            } else {
                PayRate::query()->create($payload);
                session()->flash('success', 'Pay rate created successfully.');
            }
        } catch (DomainException $exception) {
            $this->addError('rate_amount', $exception->getMessage());

            return;
        }

        $this->redirectRoute('admin.payroll.rates.index', navigate: true);
    }

    public function render(): View
    {
        return view('payroll::livewire.admin.pay-rates.form', [
            'profiles' => PayrollEmployeeProfile::query()
                ->with('user:id,first_name,last_name')
                ->orderBy('employee_number')
                ->get(['id', 'user_id', 'employee_number']),
            'rateTypes' => PayRateType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'key']),
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
        ]);
    }
}
