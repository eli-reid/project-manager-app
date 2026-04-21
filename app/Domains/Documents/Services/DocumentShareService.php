<?php

namespace App\Domains\Documents\Services;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DocumentShareService
{
    /**
     * Create a new share for a document.
     *
     * @param  array{
     *     password?: string,
     *     expires_at?: Carbon,
     *     max_downloads?: int,
     *     access_notes?: string
     * }  $options
     */
    public function createShare(Document $document, User $creator, array $options = []): DocumentShare
    {
        $sharePassword = null;
        if (! empty($options['password'])) {
            $sharePassword = hash('sha256', $options['password']);
        }

        return DocumentShare::create([
            'document_id' => $document->id,
            'created_by_id' => $creator->id,
            'share_token' => DocumentShare::generateShareToken(),
            'share_password' => $sharePassword,
            'expires_at' => $options['expires_at'] ?? null,
            'max_downloads' => $options['max_downloads'] ?? null,
            'is_active' => true,
            'access_notes' => $options['access_notes'] ?? null,
        ]);
    }

    /**
     * Toggle share active status.
     */
    public function toggleShare(DocumentShare $share, bool $active): DocumentShare
    {
        $share->update(['is_active' => $active]);

        return $share;
    }

    /**
     * Update share expiration.
     */
    public function updateExpiration(DocumentShare $share, ?Carbon $expiresAt): DocumentShare
    {
        $share->update(['expires_at' => $expiresAt]);

        return $share;
    }

    /**
     * Update download limit.
     */
    public function updateDownloadLimit(DocumentShare $share, ?int $maxDownloads): DocumentShare
    {
        $share->update(['max_downloads' => $maxDownloads]);

        return $share;
    }

    /**
     * Get all shares for a document.
     */
    public function getDocumentShares(Document $document)
    {
        return $document->shares()->with('createdBy')->get();
    }

    /**
     * Find a share by token (only active shares).
     */
    public function findShareByToken(string $token): ?DocumentShare
    {
        return DocumentShare::where('share_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Record a download and increment counter.
     */
    public function recordDownload(DocumentShare $share): void
    {
        if ($share->isValid()) {
            $share->recordDownload();
        }
    }

    /**
     * Delete a share.
     */
    public function deleteShare(DocumentShare $share): void
    {
        $share->forceDelete();
    }

    /**
     * Clean up expired shares (admin task).
     */
    public function cleanupExpiredShares(): int
    {
        return DocumentShare::where(function (Builder $query): void {
            $query->where('is_active', false)
                ->orWhere(function (Builder $subQuery): void {
                    $subQuery->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());
                })
                ->orWhere(function (Builder $subQuery): void {
                    $subQuery->whereNotNull('max_downloads')
                        ->whereColumn('download_count', '>=', 'max_downloads');
                });
        })->delete();
    }
}
