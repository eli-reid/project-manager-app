<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Payroll\Database\Factories\PayRateTypeFactory;
use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPayRateType
 */
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

    protected static function booted(): void
    {
        static::deleting(function (PayRateType $type): void {
            if ($type->is_system) {
                throw new DomainException("System pay rate type [{$type->key}] cannot be deleted.");
            }
        });

        static::updating(function (PayRateType $type): void {
            if ($type->is_system && $type->isDirty('key')) {
                throw new DomainException("The key of system pay rate type [{$type->getOriginal('key')}] cannot be changed.");
            }
        });
    }

    protected static function newFactory(): PayRateTypeFactory
    {
        return PayRateTypeFactory::new();
    }
}
