<?php

namespace App\Domains\Stock\Models;

use App\Core\User\Models\User;
use App\Domains\Stock\Database\Factories\StockOrderTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperStockOrderTemplate
 */
class StockOrderTemplate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const URGENCY_LOW = 'low';

    public const URGENCY_MEDIUM = 'medium';

    public const URGENCY_HIGH = 'high';

    protected $fillable = [
        'name',
        'description',
        'urgency',
        'notes',
        'template_items',
        'is_active',
        'is_global',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template_items' => 'array',
            'is_active' => 'boolean',
            'is_global' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('is_global', true);
    }

    public function scopeAvailableToUser(Builder $query, string $userId): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($userId): void {
            $innerQuery->where('is_global', true)
                ->orWhere('created_by', $userId);
        });
    }

    public function isOwnedBy(string $userId): bool
    {
        return (string) $this->created_by === $userId;
    }

    public function isAvailableTo(string $userId): bool
    {
        return $this->is_active && ($this->is_global || $this->isOwnedBy($userId));
    }

    protected static function newFactory(): StockOrderTemplateFactory
    {
        return StockOrderTemplateFactory::new();
    }
}
