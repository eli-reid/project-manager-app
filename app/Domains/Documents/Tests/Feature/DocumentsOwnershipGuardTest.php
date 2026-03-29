<?php

use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\QueryException;

it('enforces database xor ownership guard across owner pivot tables', function (): void {
    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
    ]);

    $ownerUser = userWithDocumentDomainPermissions(['documents.view']);
    $project = Project::factory()->create();

    $document->ownerUsers()->sync([$ownerUser->id]);

    expect(function () use ($document, $project): void {
        $document->ownerProjects()->syncWithoutDetaching([$project->id]);
    })->toThrow(QueryException::class);

    expect($document->ownerProjects()->where('projects.id', $project->id)->exists())->toBeFalse();
});
