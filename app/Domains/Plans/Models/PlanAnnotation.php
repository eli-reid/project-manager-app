<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Database\Factories\PlanAnnotationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanAnnotation extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = ['plan_sheet_id', 'plan_sheet_revision_id', 'author_id', 'type', 'geometry', 'style', 'content', 'status', 'linked_task_id', 'visibility'];

    protected $attributes = ['status' => 'open', 'visibility' => self::VISIBILITY_PUBLIC];

    protected function casts(): array
    {
        return ['geometry' => 'array', 'style' => 'array'];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(PlanSheet::class, 'plan_sheet_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(PlanSheetRevision::class, 'plan_sheet_revision_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PlanAnnotationComment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /**
     * Constrain to annotations visible to the given user: every public note, plus
     * private notes the user authored or is permitted to moderate.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('visibility', self::VISIBILITY_PUBLIC)
                ->orWhere('author_id', $user->id)
                ->when($user->hasPermission('plans.manage-annotations'), fn (Builder $query): Builder => $query->orWhere('visibility', self::VISIBILITY_PRIVATE));
        });
    }

    protected static function newFactory(): PlanAnnotationFactory
    {
        return PlanAnnotationFactory::new();
    }
}
