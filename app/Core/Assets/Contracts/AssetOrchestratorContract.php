<?php

<<<<<<< HEAD
declare(strict_types=1);

namespace App\Core\Assets\Contracts;

use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Assets\Models\Asset;
use App\Core\Identity\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Asset lifecycle orchestration.
 *
 * Callers are responsible for their own business metadata; this contract only
 * manages the stored blob and the references that point at it.
 */
interface AssetOrchestratorContract
{
    /**
     * Store an uploaded file and attach an owning reference.
     *
     * When the meta requests deduplication and an asset with the same content
     * hash already exists on the target disk, the existing asset is reused and
     * only a new reference row is created.
     */
    public function upload(
        User $uploader,
        UploadedFile $file,
        AssetReferenceTarget $target,
        ?AssetMeta $meta = null,
    ): Asset;

    /**
     * Replace the stored blob backing an asset, preserving its id and references.
     */
    public function replaceFile(Asset $asset, UploadedFile $file, ?AssetMeta $meta = null): Asset;

    /**
     * Relocate the stored blob to a new folder path.
     */
    public function move(Asset $asset, ?string $folderPath): Asset;

    /**
     * Detach one reference. The underlying blob and asset row are only removed
     * once no references remain.
     */
    public function release(Asset $asset, AssetReferenceTarget $target): bool;

    /**
     * Remove the asset, all of its references, and the stored blob.
     */
    public function purge(Asset $asset): bool;

    /**
     * Authoritative upload constraints for a referencer type.
     *
     * @return array{max_kilobytes:int, allowed_extensions:array<int, string>}
     */
    public function validationRules(?string $referencerType = null): array;
=======
namespace App\Core\Assets\Contracts;

use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\Models\Asset;
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
>>>>>>> production
}
