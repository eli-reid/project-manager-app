<?php

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;

it('redirects legacy mobile projects index URL to the current mobile route', function (): void {
    $this->actingAs(User::factory()->createOne())
        ->get('/projects/mobile')
        ->assertRedirect(route('projects.mobile.index'));
});

it('redirects legacy mobile project detail URLs to the current mobile show route', function (): void {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->createOne())
        ->get('/projects/mobile/'.$project->id)
        ->assertRedirect(route('projects.mobile.show', ['project' => $project->id]));

    $this->actingAs(User::factory()->createOne())
        ->get('/projects/mobile/'.$project->id.'/tasks')
        ->assertRedirect(route('projects.mobile.show', ['project' => $project->id]));
});
