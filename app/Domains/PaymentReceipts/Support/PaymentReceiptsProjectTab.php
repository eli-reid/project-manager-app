<?php

namespace App\Domains\PaymentReceipts\Support;

use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class PaymentReceiptsProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'payment-receipts',
            label: 'Pay Recs',
            sort: 115,
            panel: new LivewireComponentTabPanel(
                component: 'payment-receipts::admin.projects.payment-receipt-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', PaymentReceipt::class);
    }

    public function badgeCountRelations(User $user, Project $project): array
    {
        return ['paymentReceipts'];
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        $count = $project->getAttribute('payment_receipts_count');

        return is_numeric($count)
            ? (int) $count
            : $project->paymentReceipts()->count();
    }
}
