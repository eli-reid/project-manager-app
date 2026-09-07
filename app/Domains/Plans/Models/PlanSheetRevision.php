<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Domains\Plans\Database\Factories\PlanSheetRevisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanSheetRevision extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'plan_sheet_id', 'plan_set_id', 'revision_label', 'page_number', 'thumbnail_path', 'preview_path',
        'tile_manifest', 'width', 'height', 'rotation', 'text_layer', 'detected_sheet_number', 'detected_title',
        'detection_confidence', 'detection_source', 'status', 'error_message', 'is_current', 'published_at',
    ];

    protected $attributes = ['status' => 'pending', 'is_current' => false, 'rotation' => 0];

    protected function casts(): array
    {
        return [
            'tile_manifest' => 'array', 'detection_confidence' => 'decimal:4', 'is_current' => 'boolean',
            'published_at' => 'datetime', 'rotation' => 'integer', 'page_number' => 'integer',
        ];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(PlanSheet::class, 'plan_sheet_id');
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(PlanSet::class, 'plan_set_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(PlanLink::class, 'from_revision_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(PlanAnnotation::class, 'plan_sheet_revision_id');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    protected static function newFactory(): PlanSheetRevisionFactory
    {
        return PlanSheetRevisionFactory::new();
    }
}
