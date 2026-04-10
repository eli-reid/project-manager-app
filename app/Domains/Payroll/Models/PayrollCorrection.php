<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayrollCorrectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayrollCorrection
 */
class PayrollCorrection extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_REVERSAL = 'reversal';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'payroll_record_id',
        'type',
        'status',
        'amount',
        'description',
        'reason',
        'approved_at',
        'approved_by',
        'applied_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime:nullable',
            'applied_at' => 'datetime:nullable',
        ];
    }

    /**
     * Get the payroll record this correction applies to.
     */
    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    /**
     * Get the user who approved this correction.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this correction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this correction.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get pending corrections.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved corrections.
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
        return PayrollCorrectionFactory::new();
    }
}
