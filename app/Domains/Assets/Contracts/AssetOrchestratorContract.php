<?php

namespace App\Domains\Assets\Contracts;

use App\Domains\Assets\Models\Asset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;

interface AssetOrchestratorContract
{
    /**
     * Upload a generic asset. Caller (owning domain) is responsible for attaching
     * domain-specific pivot records.
     */
    public function uploadAsset(Authenticatable $uploader, UploadedFile $file, array $meta = []): Asset;

    public function replaceFile(Asset $asset, UploadedFile $file): Asset;

    public function moveAsset(Asset $asset, ?string $folderPath): Asset;

    public function deleteAsset(Asset $asset): bool;

    public function validationRules(): array;
}
