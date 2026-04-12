<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Payroll\Database\Factories\PayRateTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayRateType extends Model
{
    /** @use HasFactory<PayRateTypeFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_active',
        'is_system',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function payRates(): HasMany
    {
        return $this->hasMany(PayRate::class);
    }

    protected static function newFactory(): PayRateTypeFactory
    {
        return PayRateTypeFactory::new();
    }
}
