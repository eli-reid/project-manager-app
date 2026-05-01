<?php

namespace App\Domains\Dailies\Livewire\Dashboard;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $canViewAll = $user->can('viewAll', DailyReport::class);

        $baseQuery = DailyReport::query();

        if (! $canViewAll) {
            $baseQuery->where('user_id', $user->id);
        }

        $draftCount = (clone $baseQuery)
            ->where('status', DailyReport::STATUS_DRAFT)
            ->count();

        $submittedCount = (clone $baseQuery)
            ->where('status', DailyReport::STATUS_SUBMITTED)
            ->count();

        $approvedCount = (clone $baseQuery)
            ->where('status', DailyReport::STATUS_APPROVED)
            ->count();

        $reports = (clone $baseQuery)
            ->with(['project:id,name'])
            ->latest('report_date')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('dailies::livewire.dashboard.widget', [
            'reports' => $reports,
            'draftCount' => $draftCount,
            'submittedCount' => $submittedCount,
            'approvedCount' => $approvedCount,
            'canViewAll' => $canViewAll,
        ]);
    }
}
