<?php

namespace App\Domains\Documents\Models;

use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperDocumentShare
 */
class DocumentShare extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'document_id',
        'created_by_id',
        'share_token',
        'share_password',
        'expires_at',
        'max_downloads',
        'download_count',
        'is_active',
        'access_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'max_downloads' => 'integer',
            'download_count' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Generate a unique share token.
     */
    public static function generateShareToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Check if share is still valid (not expired, download limit not reached).
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->max_downloads !== null && $this->download_count >= $this->max_downloads) {
            return false;
        }

        return true;
    }

    /**
     * Check if share requires password verification.
     */
    public function requiresPassword(): bool
    {
        return $this->share_password !== null;
    }

    /**
     * Verify password for this share.
     */
    public function verifyPassword(string $password): bool
    {
        return $this->share_password === hash('sha256', $password);
    }

    /**
     * Increment download count.
     */
    public function recordDownload(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get expiration reason if share is expired.
     */
    public function getExpirationReason(): string
    {
        if (! $this->is_active) {
            return 'This share has been disabled.';
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return 'This share link has expired.';
        }

        if ($this->max_downloads !== null && $this->download_count >= $this->max_downloads) {
            return 'This share has reached its download limit.';
        }

        return 'This share is no longer available.';
    }
}
