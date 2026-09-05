<?php

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;

final class OverviewProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'overview',
            label: 'Overview',
            sort: 10,
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return true;
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return null;
    }
}
