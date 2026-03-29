<?php

namespace App\Core\Cpanel\Models;

use App\Core\Cpanel\Database\Factories\CachedEmailAccountFactory;
use App\Core\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CachedEmailAccount extends Model
{
    /** @use HasFactory<CachedEmailAccountFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'email',
        'domain',
        'suspended',
        'quota',
        'usage',
        'usage_percentage',
        'raw_data',
        'user_id',
        'last_synced_at',
        'sync_failed',
        'sync_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended' => 'boolean',
            'quota' => 'integer',
            'usage' => 'integer',
            'usage_percentage' => 'decimal:2',
            'raw_data' => 'array',
            'last_synced_at' => 'datetime',
            'sync_failed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('suspended', false);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('suspended', true);
    }

    public function scopeHighUsage(Builder $query, float $threshold = 80.0): Builder
    {
        return $query->where('usage_percentage', '>=', $threshold);
    }

    public function scopeSyncFailed(Builder $query): Builder
    {
        return $query->where('sync_failed', true);
    }

    protected static function newFactory(): CachedEmailAccountFactory
    {
        return CachedEmailAccountFactory::new();
    }
}
