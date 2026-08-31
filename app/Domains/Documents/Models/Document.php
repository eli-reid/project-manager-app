<?php

namespace App\Domains\Documents\Models;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Database\Factories\DocumentFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

/**
 * @mixin IdeHelperDocument
 */
class Document extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected static ?bool $internalSharesTableExists = null;

    public const OWNER_SCOPE_USER = 'user';

    public const OWNER_SCOPE_PROJECT = 'project';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_GLOBAL = 'global';

    public const VISIBILITY_PROJECT = 'project';

    public const REPLACE_MODE_REPLACE = 'replace';

    public const REPLACE_MODE_KEEP_HISTORY = 'keep-history';

    protected $fillable = [
        'title',
        'description',
        'folder_path',
        'original_name',
        'stored_name',
        'extension',
        'mime_type',
        'file_size',
        'storage_disk',
        'storage_path',
        'asset_id',
        'owner_scope',
        'owner_id',
        'visibility',
        'replace_mode',
        'uploaded_by_id',
        'last_replaced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'last_replaced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo(name: 'owner', type: 'owner_scope', id: 'owner_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function ownerProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'owner_id');
    }

    public function externalShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function shares(): HasMany
    {
        return $this->externalShares();
    }

    public function internalShares(): HasMany
    {
        return $this->hasMany(DocumentInternalShare::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Assets\Models\Asset::class, 'asset_id');
    }

    public function scopeUserOwned(Builder $query): Builder
    {
        return $query->where('owner_scope', self::OWNER_SCOPE_USER);
    }

    public function scopeProjectOwned(Builder $query): Builder
    {
        return $query->where('owner_scope', self::OWNER_SCOPE_PROJECT);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_GLOBAL);
    }

    public function scopeOwnedByUser(Builder $query, string $userId): Builder
    {
        return $query->where('owner_scope', self::OWNER_SCOPE_USER)
            ->where('owner_id', $userId);
    }

    public function scopeOwnedByProject(Builder $query, string $projectId): Builder
    {
        return $query->where('owner_scope', self::OWNER_SCOPE_PROJECT)
            ->where('owner_id', $projectId);
    }

    public function scopeSharedWithUser(Builder $query, string $userId): Builder
    {
        if (! self::internalSharesTableExists()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('internalShares', function (Builder $shareQuery) use ($userId): void {
            $shareQuery->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_USER)
                ->where('grantee_id', $userId);
        });
    }

    public function scopeSharedWithProject(Builder $query, string $projectId): Builder
    {
        if (! self::internalSharesTableExists()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('internalShares', function (Builder $shareQuery) use ($projectId): void {
            $shareQuery->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_PROJECT)
                ->where('grantee_id', $projectId);
        });
    }

    public static function internalSharesTableExists(): bool
    {
        if (self::$internalSharesTableExists !== null) {
            return self::$internalSharesTableExists;
        }

        self::$internalSharesTableExists = Schema::hasTable('document_internal_shares');

        return self::$internalSharesTableExists;
    }

    public function isUserOwned(): bool
    {
        return $this->owner_scope === self::OWNER_SCOPE_USER;
    }

    public function isProjectOwned(): bool
    {
        return $this->owner_scope === self::OWNER_SCOPE_PROJECT;
    }

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
