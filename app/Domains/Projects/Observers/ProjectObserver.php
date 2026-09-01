<?php

namespace App\Domains\Projects\Observers;

use App\Domains\Projects\Events\ProjectAddressChanged;
use App\Domains\Projects\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        if (filled($project->address_id)) {
            event(new ProjectAddressChanged($project));
        }
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged('address_id') && filled($project->address_id)) {
            event(new ProjectAddressChanged($project));
        }
    }
}
