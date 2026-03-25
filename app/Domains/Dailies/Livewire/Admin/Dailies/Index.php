<?php

namespace App\Domains\Dailies\Livewire\Admin\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Dailies')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAll', DailyReport::class);
    }

    public function render()
    {
        return view('dailies::livewire.admin.dailies.index', [
            'reports' => DailyReport::query()
                ->with(['project', 'user', 'submittedBy'])
                ->latest('report_date')
                ->paginate(15),
        ]);
    }
}
