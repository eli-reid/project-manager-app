<?php

namespace App\Domains\Dailies\Livewire\Admin\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('timecards::layouts.time-management-admin')]
#[Title('Dailies')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAll', DailyReport::class);
    }

    private function baseQuery(): Builder
    {
        $query = DailyReport::query()
            ->with(['project', 'user'])
            ->latest('report_date');

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('report_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('report_date', '<=', $this->dateTo);
        }

        return $query;
    }

    public function render()
    {
        return view('dailies::livewire.admin.dailies.index', [
            'reports' => $this->baseQuery()->paginate(15),
        ]);
    }
}
