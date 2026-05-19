<?php

namespace App\Domains\Dailies\Livewire\User\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Daily Reports')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $status = '';

    public ?string $from_date = null;

    public ?string $to_date = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', DailyReport::class);
    }

    private function isMobileRoute(): bool
    {
        return request()->routeIs('dailies.mobile.*');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $query = DailyReport::query()
            ->where('user_id', $user->id)
            ->with(['project', 'submittedBy'])
            ->latest('report_date');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (filled($this->from_date)) {
            $query->whereDate('report_date', '>=', $this->from_date);
        }

        if (filled($this->to_date)) {
            $query->whereDate('report_date', '<=', $this->to_date);
        }

        $view = $this->isMobileRoute()
            ? 'dailies::livewire.mobile.dailies.index'
            : 'dailies::livewire.user.dailies.index';

        return view($view, [
            'reports' => $query->paginate(15),
            'statuses' => [
                DailyReport::STATUS_DRAFT,
                DailyReport::STATUS_SUBMITTED,
                DailyReport::STATUS_APPROVED,
                DailyReport::STATUS_REJECTED,
            ],
        ])->layout($this->isMobileRoute() ? 'layouts.mobile' : 'layouts.app');
    }
}
