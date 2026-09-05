<?php

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Settings::set('assets.deduplicate', 'true');

    $this->orchestrator = app(AssetOrchestratorContract::class);
    $this->uploader = User::factory()->create();
    $this->targetFor = fn (string $id, string $role = 'primary'): AssetReferenceTarget => new AssetReferenceTarget('fake-domain', $id, $role);
});

afterEach(function (): void {
    Settings::set('assets.deduplicate', 'true');
});

it('stores an uploaded file and creates one reference', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf'),
        ($this->targetFor)('record-1'),
    );

    expect($asset->original_name)->toBe('plan.pdf')
        ->and($asset->content_hash)->not->toBeNull()
        ->and($asset->created_by_id)->toBe($this->uploader->id)
        ->and($asset->references()->count())->toBe(1);

    Storage::disk('local')->assertExists($asset->storage_path);
});

it('normalizes the folder path into the storage path', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf'),
        ($this->targetFor)('record-1'),
        AssetMeta::fromArray(['folder_path' => 'plans//civil']),
    );

    expect($asset->folder_path)->toBe('plans/civil')
        ->and($asset->storage_path)->toStartWith('plans/civil/');
});

it('reuses an existing blob when content matches and dedupe is enabled', function (): void {
    $first = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    $second = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('b.pdf', 'identical-content'),
        ($this->targetFor)('record-2'),
    );

    expect($second->id)->toBe($first->id)
        ->and(Asset::query()->count())->toBe(1)
        ->and(AssetReference::query()->count())->toBe(2);
});

it('stores a separate blob when dedupe is disabled', function (): void {
    $first = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    $second = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('b.pdf', 'identical-content'),
        ($this->targetFor)('record-2'),
        AssetMeta::fromArray(['dedupe_by_hash' => false]),
    );

    expect($second->id)->not->toBe($first->id)
        ->and(Asset::query()->count())->toBe(2);
});

it('stores a separate blob when dedupe is disabled globally by setting', function (): void {
    Settings::set('assets.deduplicate', 'false');

    $first = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    $second = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('b.pdf', 'identical-content'),
        ($this->targetFor)('record-2'),
    );

    expect($second->id)->not->toBe($first->id)
        ->and(Asset::query()->count())->toBe(2);
});

it('lets an explicit AssetMeta preference override the global dedupe setting', function (): void {
    Settings::set('assets.deduplicate', 'false');

    $first = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    $second = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('b.pdf', 'identical-content'),
        ($this->targetFor)('record-2'),
        AssetMeta::fromArray(['dedupe_by_hash' => true]),
    );

    expect($second->id)->toBe($first->id)
        ->and(Asset::query()->count())->toBe(1);
});

it('does not duplicate a reference when the same target uploads twice', function (): void {
    $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'identical-content'),
        ($this->targetFor)('record-1'),
    );

    expect(AssetReference::query()->count())->toBe(1);
});

it('replaces the blob while preserving the asset id and references', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('old.pdf', 'old-content'),
        ($this->targetFor)('record-1'),
    );

    $originalPath = $asset->storage_path;

    $replaced = $this->orchestrator->replaceFile(
        $asset,
        UploadedFile::fake()->createWithContent('new.pdf', 'new-content'),
    );

    expect($replaced->id)->toBe($asset->id)
        ->and($replaced->original_name)->toBe('new.pdf')
        ->and($replaced->references()->count())->toBe(1);

    Storage::disk('local')->assertMissing($originalPath);
    Storage::disk('local')->assertExists($replaced->storage_path);
});

it('moves the blob to a new folder', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->create('plan.pdf', 12),
        ($this->targetFor)('record-1'),
    );

    $originalPath = $asset->storage_path;
    $moved = $this->orchestrator->move($asset, 'archive/2026');

    expect($moved->folder_path)->toBe('archive/2026')
        ->and($moved->storage_path)->toStartWith('archive/2026/');

    Storage::disk('local')->assertMissing($originalPath);
    Storage::disk('local')->assertExists($moved->storage_path);
});

it('keeps the blob when releasing one of several references', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'shared'),
        ($this->targetFor)('record-1'),
    );

    $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->createWithContent('a.pdf', 'shared'),
        ($this->targetFor)('record-2'),
    );

    $this->orchestrator->release($asset, ($this->targetFor)('record-1'));

    expect(Asset::query()->count())->toBe(1)
        ->and(AssetReference::query()->count())->toBe(1);

    Storage::disk('local')->assertExists($asset->storage_path);
});

it('purges the blob when the last reference is released', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->create('only.pdf', 12),
        ($this->targetFor)('record-1'),
    );

    $path = $asset->storage_path;

    $this->orchestrator->release($asset, ($this->targetFor)('record-1'));

    expect(Asset::query()->count())->toBe(0)
        ->and(AssetReference::query()->count())->toBe(0);

    Storage::disk('local')->assertMissing($path);
});

it('cascades reference deletion when an asset is purged', function (): void {
    $asset = $this->orchestrator->upload(
        $this->uploader,
        UploadedFile::fake()->create('a.pdf', 12),
        ($this->targetFor)('record-1'),
    );

    $this->orchestrator->purge($asset);

    expect(AssetReference::query()->count())->toBe(0);
});
