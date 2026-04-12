<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use Database\Factories\Domains\Payroll\Models\PayrollStatementFactory;
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollEmployeeProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeProfile::class);
    }

    protected static function newFactory(): PayrollStatementFactory
    {
        return PayrollStatementFactory::new();
    }
}
