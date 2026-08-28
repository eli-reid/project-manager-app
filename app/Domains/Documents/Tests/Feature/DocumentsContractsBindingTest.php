<?php

use App\Core\Files\Contracts\FilePathNormalizerContract;
use App\Core\Files\Contracts\FileStorageContract;
use App\Core\Files\Services\DefaultFilePathNormalizer;
use App\Core\Files\Services\LaravelFileStorage;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Contracts\DocumentSharingContract;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Services\DocumentService;
use App\Domains\Documents\Services\DocumentShareService;
use App\Domains\Documents\Services\ProjectDocumentLibrary;

it('resolves core files contracts from the container', function (): void {
    expect(app(FileStorageContract::class))->toBeInstanceOf(LaravelFileStorage::class);
    expect(app(FilePathNormalizerContract::class))->toBeInstanceOf(DefaultFilePathNormalizer::class);
});

it('resolves documents orchestration contracts from the container', function (): void {
    expect(app(DocumentOrchestratorContract::class))->toBeInstanceOf(DocumentService::class);
    expect(app(DocumentSharingContract::class))->toBeInstanceOf(DocumentShareService::class);
    expect(app(ProjectDocumentLibraryContract::class))->toBeInstanceOf(ProjectDocumentLibrary::class);
});

it('normalizes folder paths through the core file path normalizer', function (): void {
    $normalizer = app(FilePathNormalizerContract::class);

    expect($normalizer->normalize(' Drawings\\Issued//IFC '))->toBe('Drawings/Issued/IFC');
    expect($normalizer->normalize(''))->toBeNull();
    expect($normalizer->normalize(null))->toBeNull();
});
