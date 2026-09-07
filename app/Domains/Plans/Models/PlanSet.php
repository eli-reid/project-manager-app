<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Core\Assets\Models\Asset;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Database\Factories\PlanSetFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanSet extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SPLITTING = 'splitting';

    public const STATUS_RENDERING = 'rendering';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_id', 'name', 'discipline', 'issued_at', 'source_asset_id', 'status',
        'page_count', 'processed_page_count', 'error_message', 'uploaded_by_id',
    ];

    protected $attributes = ['status' => self::STATUS_PENDING, 'page_count' => 0, 'processed_page_count' => 0];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'page_count' => 'integer', 'processed_page_count' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'source_asset_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PlanSheetRevision::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function progressPercent(): int
    {
        return $this->page_count > 0 ? min(100, (int) round(($this->processed_page_count / $this->page_count) * 100)) : 0;
    }

    protected static function newFactory(): PlanSetFactory
    {
        return PlanSetFactory::new();
    }
}
