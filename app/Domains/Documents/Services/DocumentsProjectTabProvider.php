<?php

namespace App\Domains\Documents\Services;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;

class DocumentsProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'documents',
                label: 'Library',
                sort: 90,
                modeParam: null,
                detailQueryParam: null,
                panel: null,
                badgeResolver: static fn (): ?int => null,
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAny', Document::class),
            ),
        ];
    }
}
