<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanLink;

final class PlanLinkService
{
    public function confirm(User $user, PlanLink $link): PlanLink
    {
        abort_unless($user->can('confirm', $link), 403);
        $link->update(['auto_detected' => false]);

        return $link->fresh();
    }
}
