<?php

namespace App\Domains\Documents\Support;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class DocumentsProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'documents',
            label: 'Library',
            sort: 90,
            panel: new LivewireComponentTabPanel(
                component: 'projects::admin.projects.assets-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', Document::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return null;
    }
}
