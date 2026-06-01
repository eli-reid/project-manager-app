<?php

use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('stores default change order document metadata on pivot sync', function (): void {
    $project = Project::factory()->create();

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
    ]);

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
    ]);

    $changeOrder->documents()->sync([
        (string) $document->id => [
            'document_role' => ChangeOrder::DOCUMENT_ROLE_REFERENCE,
            'document_status' => ChangeOrder::DOCUMENT_STATUS_ACTIVE,
            'revision' => null,
            'discipline' => null,
        ],
    ]);

    $pivot = DB::table('change_order_documents')
        ->where('change_order_id', (string) $changeOrder->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(ChangeOrder::DOCUMENT_ROLE_REFERENCE);
    expect($pivot?->document_status)->toBe(ChangeOrder::DOCUMENT_STATUS_ACTIVE);
    expect($pivot?->revision)->toBeNull();
    expect($pivot?->discipline)->toBeNull();
});

it('stores custom change order document metadata on pivot sync', function (): void {
    $project = Project::factory()->create();

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
    ]);

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
    ]);

    $changeOrder->documents()->sync([
        (string) $document->id => [
            'document_role' => ChangeOrder::DOCUMENT_ROLE_SUPPORTING,
            'document_status' => ChangeOrder::DOCUMENT_STATUS_SUPERSEDED,
            'revision' => 'Rev D',
            'discipline' => 'Civil',
        ],
    ]);

    $pivot = DB::table('change_order_documents')
        ->where('change_order_id', (string) $changeOrder->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(ChangeOrder::DOCUMENT_ROLE_SUPPORTING);
    expect($pivot?->document_status)->toBe(ChangeOrder::DOCUMENT_STATUS_SUPERSEDED);
    expect($pivot?->revision)->toBe('Rev D');
    expect($pivot?->discipline)->toBe('Civil');
});

it('exposes allowed change order document roles and statuses', function (): void {
    expect(ChangeOrder::allowedDocumentRoles())
        ->toContain(ChangeOrder::DOCUMENT_ROLE_REFERENCE, ChangeOrder::DOCUMENT_ROLE_SUPPORTING);

    expect(ChangeOrder::allowedDocumentStatuses())
        ->toContain(ChangeOrder::DOCUMENT_STATUS_ACTIVE, ChangeOrder::DOCUMENT_STATUS_SUPERSEDED);
});

it('creates the change order documents metadata index with a short explicit name', function (): void {
    expect(Schema::hasIndex('change_order_documents', 'cod_coid_role_status_idx'))->toBeTrue();
});
