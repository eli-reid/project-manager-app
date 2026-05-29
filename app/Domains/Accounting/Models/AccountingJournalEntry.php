<?php

namespace App\Domains\Accounting\Models;

use App\Domains\Accounting\Database\Factories\AccountingJournalEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAccountingJournalEntry
 */
class AccountingJournalEntry extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'entry_number',
        'description',
        'source_type',
        'source_id',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class)->orderBy('line_number');
    }

    public function totalDebits(): float
    {
        return (float) $this->lines->sum('debit_amount');
    }

    public function totalCredits(): float
    {
        return (float) $this->lines->sum('credit_amount');
    }

    protected static function newFactory(): AccountingJournalEntryFactory
    {
        return AccountingJournalEntryFactory::new();
    }
}
