<?php

use App\Domains\Assets\DTOs\AssetMeta;
use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Services\AssetService;
use App\Core\Assets\Files\Contracts\FilePathNormalizerContract;
use App\Core\Assets\Files\Contracts\FileStorageContract;
use App\Core\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Mockery;

test('uploadAsset stores file and creates asset record', function () {
    // Arrange
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $meta = AssetMeta::fromArray([
        'folder_path' => 'projects/42',
        'disk' => 'local',
    ]);

    $expectedDirectory = 'projects/42';
    $expectedDisk = 'local';

    $storage = Mockery::mock(FileStorageContract::class);
    $storage->shouldReceive('store')
        ->once()
        ->withArgs(function ($receivedFile, $directory, $disk) use ($expectedDirectory, $expectedDisk) {
            // ensure we received the uploaded file and expected storage hints
            return $receivedFile instanceof UploadedFile && $directory === $expectedDirectory && $disk === $expectedDisk;
        })
        ->andReturn('projects/42/document.pdf');

    $normalizer = Mockery::mock(FilePathNormalizerContract::class);
    $normalizer->shouldReceive('normalize')
        ->once()
        ->with('projects/42')
        ->andReturn('projects/42');

    $service = new AssetService($storage, $normalizer);

    // Act
    $asset = $service->uploadAsset($user, $file, $meta);

    // Assert
    expect(Asset::count())->toBe(1);
    expect($asset)->toBeInstanceOf(Asset::class);
    expect($asset->storage_path)->toBe('projects/42/document.pdf');
    expect($asset->folder_path)->toBe('projects/42');
    expect($asset->storage_disk)->toBe('local');
    expect($asset->original_name)->toBe('document.pdf');

    // Cleanup mockery expectations
    Mockery::close();
});
