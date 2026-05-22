<?php

use App\Domains\Projects\Models\Project;

it('redirects legacy mobile projects index URL to the current mobile route', function (): void {
    $this->get('/projects/mobile')
        ->assertRedirect(route('projects.mobile.index'));
});

it('redirects legacy mobile project detail URLs to the current mobile show route', function (): void {
    $project = Project::factory()->create();

    $this->get('/projects/mobile/'.$project->id)
        ->assertRedirect(route('projects.mobile.show', ['project' => $project->id]));

    $this->get('/projects/mobile/'.$project->id.'/tasks')
        ->assertRedirect(route('projects.mobile.show', ['project' => $project->id]));
});
