<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRates;

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Projects\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Payroll Employee Rates')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $rateTypeFilter = '';

    #[Url(as: 'project')]
    public string $projectFilter = '';

    #[Url(as: 'active')]
    public string $activeFilter = '1';

    public function mount(): void
    {
        $this->authorize('payroll-rates.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRateTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->search);
        $rateTypeFilter = $this->rateTypeFilter;
        $projectFilter = $this->projectFilter;
        $activeFilter = $this->activeFilter;

        $rates = PayRate::query()
            ->with(['payrollEmployeeProfile.user', 'payRateType', 'project'])
            ->when($rateTypeFilter !== '', fn ($query) => $query->where('pay_rate_type_id', $rateTypeFilter))
            ->when($projectFilter === 'unassigned', fn ($query) => $query->whereNull('project_id'))
            ->when($projectFilter !== '' && $projectFilter !== 'unassigned', fn ($query) => $query->where('project_id', $projectFilter))
            ->when($activeFilter === '1', fn ($query) => $query->whereNull('expiration_date'))
            ->when($activeFilter === '0', fn ($query) => $query->whereNotNull('expiration_date'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('payrollEmployeeProfile', function ($profileQuery) use ($search): void {
                    $profileQuery->where('employee_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('effective_date')
            ->paginate(20);

        return view('payroll::livewire.admin.pay-rates.index', [
            'rates' => $rates,
            'rateTypes' => PayRateType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'key']),
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'project_number']),
            'profiles' => PayrollEmployeeProfile::query()->with('user:id,first_name,last_name')->orderBy('employee_number')->get(['id', 'user_id', 'employee_number']),
        ]);
    }
}
