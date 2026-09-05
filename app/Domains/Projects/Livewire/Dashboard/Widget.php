<?php

namespace App\Domains\Projects\Livewire\Dashboard;

use App\Core\Identity\Models\User;
use App\Support\Diagnostics\MemoryProbe;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.projects.active-summary.render.start') : null;
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $closedStatuses = [
            ProjectStatusEnum::COMPLETED->value,
            ProjectStatusEnum::FINAL_INSPECTION->value,
            ProjectStatusEnum::CANCELLED->value,
            ProjectStatusEnum::ARCHIVED->value,
        ];

        $query = Project::query()
            ->where('is_active', true)
            ->whereNull('leave_category')
            ->whereNotIn('status', $closedStatuses);

        if (! $user->isAdmin()) {
            $this->applyAssignedScope($query, $user);
        }

        $total = (clone $query)->count();

        $projects = (clone $query)
            ->with(['client:id,company_name'])
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->limit(5)
            ->get();
        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'rendered', [
                'widget' => 'projects.active-summary',
                'phase' => 'render',
                'projects_count' => $projects->count(),
                'total' => $total,
                'projects_payload' => MemoryProbe::inspect($projects, 'projects'),
            ]);
        }

        return view('projects::livewire.dashboard.widget', [
            'projects' => $projects,
            'total' => $total,
        ]);
    }

    private function applyAssignedScope(Builder $query, User $user): void
    {
        $activeRoleIds = $user->roles()
            ->where('is_active', true)
            ->pluck('roles.id')
            ->all();

        $query->where(function (Builder $builder) use ($user, $activeRoleIds): void {
            $builder->where('project_manager_id', $user->id)
                ->orWhereHas('userAccesses', function (Builder $accessQuery) use ($user): void {
                    $accessQuery
                        ->where('user_id', $user->id)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });

            if ($activeRoleIds !== []) {
                $builder->orWhereHas('roleAccesses', function (Builder $accessQuery) use ($activeRoleIds): void {
                    $accessQuery
                        ->whereIn('role_id', $activeRoleIds)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });
            }
        });
    }
}
