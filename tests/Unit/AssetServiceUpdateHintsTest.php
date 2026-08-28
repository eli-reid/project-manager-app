<?php

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Services\AssetService;
use App\Domains\Assets\DTOs\AssetMeta;
use App\Core\Files\Contracts\FileStorageContract;
use App\Core\Files\Contracts\FilePathNormalizerContract;
use App\Core\Identity\Models\User;
use Mockery;

test('updateHints moves storage and updates asset fields', function () {
    $user = User::factory()->create();

    $asset = Asset::create([
        'original_name' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1234,
        'storage_disk' => 'local',
        'storage_path' => 'assets/file.pdf',
        'folder_path' => 'assets',
        'created_by_id' => $user->getAuthIdentifier(),
    ]);

    $meta = AssetMeta::fromArray([
        'folder_path' => 'projects/99',
        'disk' => 's3',
    ]);

    $expectedTarget = 'projects/99/file.pdf';

    $storage = Mockery::mock(FileStorageContract::class);
    $storage->shouldReceive('move')
        ->once()
        ->withArgs(function ($oldPath, $targetPath, $disk) use ($expectedTarget) {
            return $oldPath === 'assets/file.pdf' && $targetPath === $expectedTarget && $disk === 's3';
        })
        ->andReturnTrue();

    $normalizer = Mockery::mock(FilePathNormalizerContract::class);
    $normalizer->shouldReceive('normalize')
        ->once()
        ->with('projects/99')
        ->andReturn('projects/99');

    $service = new AssetService($storage, $normalizer);

    $updated = $service->updateHints($asset, $meta);

    expect($updated)->toBeInstanceOf(Asset::class);
    expect($updated->folder_path)->toBe('projects/99');
    expect($updated->storage_disk)->toBe('s3');
    expect($updated->storage_path)->toBe($expectedTarget);

    Mockery::close();
});
