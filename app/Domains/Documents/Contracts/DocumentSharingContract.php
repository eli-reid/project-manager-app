<?php

namespace App\Domains\Documents\Contracts;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

interface DocumentSharingContract
{
    /**
     * @param  array{
     *     password?: string,
     *     expires_at?: DateTimeInterface,
     *     max_downloads?: int,
     *     access_notes?: string
     * }  $options
     */
    public function createShare(Document $document, User $creator, array $options = []): DocumentShare;

    public function toggleShare(DocumentShare $share, bool $active): DocumentShare;

    public function updateExpiration(DocumentShare $share, ?DateTimeInterface $expiresAt): DocumentShare;

    public function updateDownloadLimit(DocumentShare $share, ?int $maxDownloads): DocumentShare;

    public function getDocumentShares(Document $document): Collection;

    public function findShareByToken(string $token): ?DocumentShare;

    public function recordDownload(DocumentShare $share): void;

    public function deleteShare(DocumentShare $share): void;

    public function cleanupExpiredShares(): int;
}
