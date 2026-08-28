<?php

namespace App\Domains\Projects\Events;

use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectAddressChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Project $project) {}
}
