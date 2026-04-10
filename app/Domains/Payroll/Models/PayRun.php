<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayRun
 */
class PayRun extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROVISIONAL = 'provisional';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'payroll_period_id',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'records_count',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'approved_at' => 'datetime:nullable',
        ];
    }

    /**
     * Get the payroll period for this run.
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get the payroll records in this run.
     */
    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /**
     * Get the user who approved this run.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this run.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this run.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get provisional runs.
     */
    public function scopeProvisional($query)
    {
        return $query->where('status', self::STATUS_PROVISIONAL);
    }

    /**
     * Scope to get approved runs.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PayRunFactory::new();
    }
}
