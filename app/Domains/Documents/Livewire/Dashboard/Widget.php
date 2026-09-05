<?php

namespace App\Domains\Documents\Livewire\Dashboard;

use App\Core\Identity\Models\User;
use App\Support\Diagnostics\MemoryProbe;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $baseline = MemoryProbe::enabled() ? MemoryProbe::snapshot('widget.documents.project-documents.render.start') : null;
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        // Get the user's active project IDs for filtering project-owned documents.
        $userProjectIds = $this->getUserAccessibleProjectIds($user);

        $documentsQuery = Document::query()
            ->where(function (Builder $builder) use ($userProjectIds) {
                $builder->where('visibility', Document::VISIBILITY_GLOBAL)
                    ->orWhere(function (Builder $projectQuery) use ($userProjectIds) {
                        if (! empty($userProjectIds)) {
                            $projectQuery->where('owner_scope', Document::OWNER_SCOPE_PROJECT)
                                ->whereIn('owner_id', $userProjectIds);
                        }
                    });
            });

        $documents = (clone $documentsQuery)
            ->with(['ownerProject:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $total = (clone $documentsQuery)->count();
        if ($baseline !== null) {
            MemoryProbe::logDelta('Dashboard widget memory probe.', $baseline, 'rendered', [
                'widget' => 'documents.project-documents',
                'phase' => 'render',
                'documents_count' => $documents->count(),
                'total' => $total,
                'user_project_ids_count' => \count($userProjectIds),
                'documents_payload' => MemoryProbe::inspect($documents, 'documents'),
            ]);
        }

        return view('documents::livewire.dashboard.widget', [
            'documents' => $documents,
            'total' => $total,
        ]);
    }

    private function getUserAccessibleProjectIds(User $user): array
    {
        // Get all projects the user is assigned to or has access to
        return Project::query()
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($user) {
                $builder->where('project_manager_id', $user->id)
                    ->orWhereHas('userAccesses', function (Builder $accessQuery) use ($user) {
                        $accessQuery->where('user_id', $user->id);
                    });

                if ($user->isAdmin()) {
                    // Admin can see all projects
                    return $builder;
                }

                // Non-admin users with role-based access
                $activeRoleIds = $user->roles()
                    ->where('is_active', true)
                    ->pluck('roles.id')
                    ->all();

                if ($activeRoleIds !== []) {
                    $builder->orWhereHas('roleAccesses', function (Builder $accessQuery) use ($activeRoleIds) {
                        $accessQuery->whereIn('role_id', $activeRoleIds);
                    });
                }
            })
            ->pluck('id')
            ->all();
    }
}
