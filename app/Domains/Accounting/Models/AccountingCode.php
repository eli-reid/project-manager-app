<?php

namespace App\Domains\Accounting\Models;

use App\Domains\Accounting\Database\Factories\AccountingCodeFactory;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAccountingCode
 */
class AccountingCode extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'revenue',
        'expense',
        'other',
    ];

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => 'string',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function stockOrders(): HasMany
    {
        return $this->hasMany(StockOrder::class);
    }

    protected static function newFactory(): AccountingCodeFactory
    {
        return AccountingCodeFactory::new();
    }
}
