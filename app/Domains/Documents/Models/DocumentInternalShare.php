<?php

namespace App\Domains\Documents\Models;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperDocumentInternalShare
 */
class DocumentInternalShare extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const GRANTEE_SCOPE_USER = 'user';

    public const GRANTEE_SCOPE_PROJECT = 'project';

    public const PERMISSION_VIEW = 'view';

    public const PERMISSION_ATTACH = 'attach';

    protected $fillable = [
        'document_id',
        'grantee_scope',
        'grantee_id',
        'permission_level',
        'granted_by_id',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }

    public function grantee(): MorphTo
    {
        return $this->morphTo(name: 'grantee', type: 'grantee_scope', id: 'grantee_id');
    }

    public function granteeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grantee_id');
    }

    public function granteeProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'grantee_id');
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('grantee_scope', self::GRANTEE_SCOPE_USER)
            ->where('grantee_id', $userId);
    }

    public function scopeForProject(Builder $query, string $projectId): Builder
    {
        return $query->where('grantee_scope', self::GRANTEE_SCOPE_PROJECT)
            ->where('grantee_id', $projectId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }
}
