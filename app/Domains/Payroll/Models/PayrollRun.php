<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public const TYPE_REGULAR = 'regular';

    public const TYPE_OFF_CYCLE = 'off-cycle';

    public const TYPE_CORRECTION = 'correction';

    public const STATUS_PREVIEW = 'preview';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'payroll_period_id',
        'run_type',
        'status',
        'approved_at',
        'approved_by',
        'finalized_at',
        'finalized_by',
        'total_gross',
        'total_deductions',
        'total_taxes',
        'total_net',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'finalized_at' => 'datetime',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_taxes' => 'decimal:2',
            'total_net' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(PayrollStatement::class);
    }

    protected static function newFactory(): PayrollRunFactory
    {
        return PayrollRunFactory::new();
    }
}
