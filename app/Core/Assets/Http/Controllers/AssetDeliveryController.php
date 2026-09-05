<?php

declare(strict_types=1);

namespace App\Core\Assets\Http\Controllers;

use App\Core\Assets\Contracts\FileStorageContract;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Services\AssetGatekeeper;
use App\Core\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The single delivery point for asset binaries.
 *
 * Authorization is delegated to the owning domain through the gatekeeper, so
 * every domain gets consistent download, preview, and range-request behaviour
 * without duplicating route handlers.
 */
class AssetDeliveryController
{
    public function __construct(
        private readonly AssetGatekeeper $gatekeeper,
        private readonly FileStorageContract $fileStorage,
    ) {}

    public function download(Request $request, Asset $asset): StreamedResponse
    {
        $this->authorizeDownload($request, $asset);

        return Storage::disk((string) $asset->storage_disk)
            ->download((string) $asset->storage_path, (string) $asset->original_name);
    }

    public function preview(Request $request, Asset $asset): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $this->gatekeeper->canView($user, $asset), 403);
        $this->assertStoredFileExists($asset);

        return Storage::disk((string) $asset->storage_disk)
            ->response(
                (string) $asset->storage_path,
                (string) $asset->original_name,
                [
                    'Content-Type' => (string) ($asset->mime_type ?: 'application/octet-stream'),
                    'Content-Disposition' => 'inline; filename="'.addslashes((string) $asset->original_name).'"',
                ],
            );
    }

    private function authorizeDownload(Request $request, Asset $asset): void
    {
        $user = $request->user();

        abort_unless($user instanceof User && $this->gatekeeper->canDownload($user, $asset), 403);
        $this->assertStoredFileExists($asset);
    }

    private function assertStoredFileExists(Asset $asset): void
    {
        abort_unless(
            $this->fileStorage->exists((string) $asset->storage_path, (string) $asset->storage_disk),
            404,
            'File not found.',
        );
    }
}
