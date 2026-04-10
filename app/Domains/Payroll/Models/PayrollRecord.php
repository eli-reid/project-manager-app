<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayrollRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayrollRecord
 */
class PayrollRecord extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'pay_run_id',
        'user_id',
        'regular_hours',
        'overtime_hours',
        'gross_amount',
        'federal_tax',
        'state_tax',
        'local_tax',
        'social_security',
        'medicare',
        'total_deductions',
        'net_amount',
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
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'federal_tax' => 'decimal:2',
            'state_tax' => 'decimal:2',
            'local_tax' => 'decimal:2',
            'social_security' => 'decimal:2',
            'medicare' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the payroll run this record belongs to.
     */
    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    /**
     * Get the employee for this payroll record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the corrections applied to this record.
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(PayrollCorrection::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PayrollRecordFactory::new();
    }
}
