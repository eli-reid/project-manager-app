<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use Database\Factories\Domains\Payroll\Models\PayrollEmployeeProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollEmployeeProfile extends Model
{
    /** @use HasFactory<PayrollEmployeeProfileFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_number',
        'ssn_encrypted',
        'date_of_birth',
        'hire_date',
        'termination_date',
        'status',
        'pay_type',
        'department',
        'job_classification',
        'union_code',
        'direct_deposit_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ssn_encrypted' => 'encrypted',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'direct_deposit_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payRates(): HasMany
    {
        return $this->hasMany(PayRate::class);
    }

    protected static function newFactory(): PayrollEmployeeProfileFactory
    {
        return PayrollEmployeeProfileFactory::new();
    }
}
