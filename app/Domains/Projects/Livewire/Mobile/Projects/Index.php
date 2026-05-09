<?php

namespace App\Domains\Projects\Livewire\Mobile\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Livewire\User\Projects\Index as UserProjectsIndex;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.mobile')]
#[Title('Projects')]
class Index extends UserProjectsIndex
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $projects = $this->projectsQuery($user)
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->paginate(10);

        return view('projects::livewire.mobile.projects.index', [
            'projects' => $projects,
        ]);
    }
}
