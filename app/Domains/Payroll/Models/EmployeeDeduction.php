<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Payroll\Database\Factories\EmployeeDeductionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDeduction extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'payroll_employee_profile_id',
        'deduction_id',
        'override_amount',
        'effective_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'override_amount' => 'decimal:4',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function payrollEmployeeProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeProfile::class);
    }

    public function deduction(): BelongsTo
    {
        return $this->belongsTo(Deduction::class);
    }

    protected static function newFactory(): EmployeeDeductionFactory
    {
        return EmployeeDeductionFactory::new();
    }
}
