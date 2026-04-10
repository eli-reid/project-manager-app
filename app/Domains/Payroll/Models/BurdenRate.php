<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\BurdenRateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperBurdenRate
 */
class BurdenRate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_USER = 'user';

    protected $fillable = [
        'user_id',
        'scope',
        'component_name',
        'percentage',
        'amount',
        'effective_date',
        'end_date',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:4',
            'amount' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date:nullable',
        ];
    }

    /**
     * Get the user this burden rate applies to (if user-scoped).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this burden rate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this burden rate.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active burden rates for a given date.
     */
    public function scopeActiveOn($query, $date = null)
    {
        $date = $date ?? now()->toDateString();

        return $query
            ->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    /**
     * Scope to get global burden rates.
     */
    public function scopeGlobal($query)
    {
        return $query->where('scope', self::SCOPE_GLOBAL)->whereNull('user_id');
    }

    /**
     * Scope to get user-specific burden rates.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('scope', self::SCOPE_USER)->where('user_id', $userId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return BurdenRateFactory::new();
    }
}
