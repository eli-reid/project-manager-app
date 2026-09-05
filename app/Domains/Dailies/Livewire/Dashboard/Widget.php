<?php

namespace App\Domains\Dailies\Livewire\Dashboard;

use App\Core\Identity\Models\User;
use App\Support\Diagnostics\MemoryProbe;
use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.dailies.field-summary.render.start') : null;
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $canViewAll = $user->can('viewAll', DailyReport::class);

        $baseQuery = DailyReport::query();

        if (! $canViewAll) {
            $baseQuery->where('user_id', $user->id);
        }

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $draftCount = (int) ($statusCounts[DailyReport::STATUS_DRAFT] ?? 0);
        $submittedCount = (int) ($statusCounts[DailyReport::STATUS_SUBMITTED] ?? 0);
        $approvedCount = (int) ($statusCounts[DailyReport::STATUS_APPROVED] ?? 0);

        $reports = (clone $baseQuery)
            ->with(['project:id,name'])
            ->latest('report_date')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $usesMobileRoutes = request()->routeIs('mobile.dashboard') && Route::has('dailies.mobile.index');

        $indexHref = $usesMobileRoutes ? route('dailies.mobile.index') : route('dailies.index');
        $reportRoute = $usesMobileRoutes && Route::has('dailies.mobile.show') ? 'dailies.mobile.show' : 'dailies.show';
        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'rendered', [
                'widget' => 'dailies.field-summary',
                'phase' => 'render',
                'reports_count' => $reports->count(),
                'can_view_all' => $canViewAll,
                'reports_payload' => MemoryProbe::inspect($reports, 'reports'),
                'status_counts' => [
                    'draft' => $draftCount,
                    'submitted' => $submittedCount,
                    'approved' => $approvedCount,
                ],
            ]);
        }

        return view('dailies::livewire.dashboard.widget', [
            'reports' => $reports,
            'draftCount' => $draftCount,
            'submittedCount' => $submittedCount,
            'approvedCount' => $approvedCount,
            'canViewAll' => $canViewAll,
            'indexHref' => $indexHref,
            'reportRoute' => $reportRoute,
        ]);
    }
}
