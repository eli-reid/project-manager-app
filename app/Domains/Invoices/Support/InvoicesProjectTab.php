<?php

namespace App\Domains\Invoices\Support;

use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class InvoicesProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'invoices',
            label: 'Invoices',
            sort: 40,
            modeParam: 'invoiceMode',
            detailQueryParam: 'invoiceId',
            panel: new LivewireComponentTabPanel(
                component: 'invoices::admin.invoices.index',
                baseProps: ['embedded' => true],
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', Invoice::class);
    }

    public function badgeCountRelations(User $user, Project $project): array
    {
        return ['invoices'];
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        $count = $project->getAttribute('invoices_count');

        return is_numeric($count)
            ? (int) $count
            : $project->invoices()->count();
    }
}
