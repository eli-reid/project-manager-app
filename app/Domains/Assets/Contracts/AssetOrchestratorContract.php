<?php

namespace App\Domains\Assets\Contracts;

use App\Domains\Assets\DTOs\AssetMeta;
use App\Domains\Assets\Models\Asset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;

interface AssetOrchestratorContract
{
    /**
     * Upload a generic asset. Caller (owning domain) is responsible for attaching
     * domain-specific pivot records.
     *
     * Supported storage hints are (optional) provided via `AssetMeta`:
     * - `folderPath` / `folder_path`: storage prefix/hint
     * - `disk`: filesystem disk name
     * - `visibility`: public|private
     * - `contentHash` / `content_hash`: file hash for deduplication
     * - `dedupeByHash` / `dedupe_by_hash`: bool hint to attempt dedupe
     * - `expiresAt` / `expires_at`: expiry date/time for retention
     *
     * Domains should persist business metadata (title, captions, associations)
     * in their own tables referencing the returned `Asset` id.
     */
    public function uploadAsset(Authenticatable $uploader, UploadedFile $file, ?AssetMeta $meta = null): Asset;

    public function replaceFile(Asset $asset, UploadedFile $file): Asset;

    public function moveAsset(Asset $asset, ?string $folderPath): Asset;

    /**
     * Update storage hints for an existing asset. This may move the underlying
     * storage object if the folder or disk is changed. Only storage-related
     * hints are considered; business metadata remains the caller's responsibility.
     */
    public function updateHints(Asset $asset, ?AssetMeta $meta): Asset;

    public function deleteAsset(Asset $asset): bool;

    public function validationRules(): array;
}
