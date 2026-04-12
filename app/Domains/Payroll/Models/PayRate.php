<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\PayRateFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayRate
 */
class PayRate extends Model
{
    /** @use HasFactory<PayRateFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'payroll_employee_profile_id',
        'pay_rate_type_id',
        'project_id',
        'rate_amount',
        'effective_date',
        'expiration_date',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_amount' => 'decimal:4',
            'effective_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function payrollEmployeeProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeProfile::class);
    }

    public function payRateType(): BelongsTo
    {
        return $this->belongsTo(PayRateType::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<PayRate>  $query
     * @return Builder<PayRate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('expiration_date');
    }

    protected static function newFactory(): PayRateFactory
    {
        return PayRateFactory::new();
    }
}
