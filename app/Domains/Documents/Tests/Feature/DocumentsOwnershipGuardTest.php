<?php

use App\Domains\Documents\Models\Document;

it('stores ownership on documents using single owner columns', function (): void {
    $ownerUser = userWithDocumentDomainPermissions(['documents.view']);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'owner_id' => $ownerUser->id,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $ownerUser->id,
    ]);

    expect($document->owner_scope)->toBe(Document::OWNER_SCOPE_USER);
    expect($document->owner_id)->toBe($ownerUser->id);
});
