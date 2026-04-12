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

    protected $fillable = [
        'pay_period_start',
        'pay_period_end',
        'pay_date',
        'status',
        'total_gross',
        'total_net',
        'total_taxes',
        'employee_count',
        'created_by',
        'approved_by',
        'finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pay_period_start' => 'date',
            'pay_period_end' => 'date',
            'pay_date' => 'date',
            'total_gross' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_taxes' => 'decimal:2',
            'employee_count' => 'integer',
            'finalized_at' => 'datetime',
        ];
    }

    public function payrollStatements(): HasMany
    {
        return $this->hasMany(PayrollStatement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function newFactory(): PayRunFactory
    {
        return PayRunFactory::new();
    }
}
