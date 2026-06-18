<?php

namespace App\Domains\Invoices\Services;

use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;

class InvoicesProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'invoices',
                label: 'Invoices',
                sort: 40,
                modeParam: 'invoiceMode',
                detailQueryParam: 'invoiceId',
                panel: null,
                badgeResolver: static fn (User $user, Project $project): ?int => Invoice::query()
                    ->where('project_id', $project->id)
                    ->count(),
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAny', Invoice::class),
            ),
        ];
    }
}
