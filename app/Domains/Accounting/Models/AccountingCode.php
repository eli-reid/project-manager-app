<?php

namespace App\Domains\Accounting\Models;

use App\Domains\Accounting\Database\Factories\AccountingCodeFactory;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public const NORMAL_BALANCES = [
        'debit',
        'credit',
    ];

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'parent_id',
        'normal_balance',
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
            'parent_id' => 'string',
            'normal_balance' => 'string',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
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

    public function journalLines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class);
    }

    protected static function newFactory(): AccountingCodeFactory
    {
        return AccountingCodeFactory::new();
    }
}
