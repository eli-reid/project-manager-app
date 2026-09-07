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

    protected $fillable = ['plan_sheet_id', 'plan_sheet_revision_id', 'author_id', 'type', 'geometry', 'style', 'content', 'status', 'linked_task_id', 'visibility'];

    protected $attributes = ['status' => 'open', 'visibility' => 'project'];

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

    protected static function newFactory(): PlanAnnotationFactory
    {
        return PlanAnnotationFactory::new();
    }
}
