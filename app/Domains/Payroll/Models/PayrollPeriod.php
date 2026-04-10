<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayrollPeriod
 */
class PayrollPeriod extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'period_start_date',
        'period_end_date',
        'status',
        'finalized_at',
        'finalized_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start_date' => 'date',
            'period_end_date' => 'date',
            'finalized_at' => 'datetime:nullable',
        ];
    }

    /**
     * Get the payroll runs for this period.
     */
    public function payRuns(): HasMany
    {
        return $this->hasMany(PayRun::class);
    }

    /**
     * Get the user who finalized this period.
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Get the user who created this period.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this period.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get open periods.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get finalized periods.
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PayrollPeriodFactory::new();
    }
}
