<?php

declare(strict_types=1);

namespace App\Core\Assets\Models;

use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An edge between a domain record and a stored blob.
 *
 * Authorization is always evaluated per reference, never per blob, so that
 * deduplicated assets shared by several domains cannot leak access.
 *
 * @mixin IdeHelperAssetReference
 */
class AssetReference extends Model
{
    protected $fillable = [
        'asset_id',
        'referencer_type',
        'referencer_id',
        'role',
        'created_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function target(): AssetReferenceTarget
    {
        return new AssetReferenceTarget(
            referencerType: (string) $this->referencer_type,
            referencerId: (string) $this->referencer_id,
            role: (string) $this->role,
        );
    }
}
