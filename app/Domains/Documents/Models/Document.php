<?php

namespace App\Domains\Documents\Models;

use App\Core\User\Models\User;
use App\Domains\Documents\Database\Factories\DocumentFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperDocument
 */
class Document extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

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
        'original_name',
        'stored_name',
        'extension',
        'mime_type',
        'file_size',
        'storage_disk',
        'storage_path',
        'owner_scope',
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

    public function ownerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user_owners', 'document_id', 'user_id')
            ->withTimestamps();
    }

    public function ownerProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'document_project_owners', 'document_id', 'project_id')
            ->withTimestamps();
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
        return $query->whereHas('ownerUsers', fn (Builder $ownerQuery): Builder => $ownerQuery->where('users.id', $userId));
    }

    public function scopeOwnedByProject(Builder $query, string $projectId): Builder
    {
        return $query->whereHas('ownerProjects', fn (Builder $ownerQuery): Builder => $ownerQuery->where('projects.id', $projectId));
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
