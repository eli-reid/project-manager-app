<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Payroll\Database\Factories\DeductionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'calculation_method',
        'amount',
        'priority',
        'pre_tax',
        'max_annual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'priority' => 'integer',
            'pre_tax' => 'boolean',
            'max_annual' => 'decimal:2',
        ];
    }

    public function employeeDeductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    protected static function newFactory(): DeductionFactory
    {
        return DeductionFactory::new();
    }
}
