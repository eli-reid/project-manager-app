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

    public function mount(): void
    {
        $this->authorize('viewAny', DailyReport::class);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        return view('dailies::livewire.user.dailies.index', [
            'reports' => DailyReport::query()
                ->where('user_id', $user->id)
                ->with(['project', 'submittedBy'])
                ->latest('report_date')
                ->paginate(15),
        ]);
    }
}
