<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayrollStatementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollStatement extends Model
{
    /** @use HasFactory<PayrollStatementFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'payroll_employee_profile_id',
        'pay_run_id',
        'total_regular_hours',
        'total_ot_hours',
        'total_dt_hours',
        'gross_pay',
        'federal_tax',
        'state_tax',
        'local_tax',
        'social_security',
        'medicare',
        'other_deductions',
        'net_pay',
        'ytd_gross',
        'ytd_federal_tax',
        'ytd_net',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_regular_hours' => 'decimal:2',
            'total_ot_hours' => 'decimal:2',
            'total_dt_hours' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'federal_tax' => 'decimal:2',
            'state_tax' => 'decimal:2',
            'local_tax' => 'decimal:2',
            'social_security' => 'decimal:2',
            'medicare' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'ytd_gross' => 'decimal:2',
            'ytd_federal_tax' => 'decimal:2',
            'ytd_net' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollEmployeeProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeProfile::class);
    }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    protected static function newFactory(): PayrollStatementFactory
    {
        return PayrollStatementFactory::new();
    }
}
