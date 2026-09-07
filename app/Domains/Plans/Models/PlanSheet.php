<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Domains\Plans\Database\Factories\PlanSheetFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanSheet extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['project_id', 'sheet_number', 'title', 'discipline', 'sort_index', 'current_revision_id'];

    protected $attributes = ['sort_index' => 0];

    protected function casts(): array
    {
        return ['sort_index' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PlanSheetRevision::class);
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(PlanSheetRevision::class, 'current_revision_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(PlanAnnotation::class);
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(PlanLink::class, 'target_sheet_id');
    }

    public function scopeForProject(Builder $query, string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    protected static function newFactory(): PlanSheetFactory
    {
        return PlanSheetFactory::new();
    }
}
