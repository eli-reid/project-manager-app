<?php

namespace App\Domains\Timecards\Livewire\Admin\Timecards;

use App\Domains\Timecards\Models\Timecard;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Timecards')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Timecard::class);
    }

    public function render()
    {
        return view('timecards::livewire.admin.timecards.index', [
            'timecards' => Timecard::query()
                ->with(['user'])
                ->latest('week_starting')
                ->paginate(15),
        ]);
    }
}
