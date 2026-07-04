<?php

namespace App\Domains\Projects\Events;

use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProjectDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Project $project)
    {
    }
}
