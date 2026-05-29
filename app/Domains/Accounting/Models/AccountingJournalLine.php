<?php

namespace App\Domains\Accounting\Models;

use App\Domains\Accounting\Database\Factories\AccountingJournalLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAccountingJournalLine
 */
class AccountingJournalLine extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'accounting_journal_entry_id',
        'accounting_code_id',
        'line_number',
        'description',
        'debit_amount',
        'credit_amount',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'accounting_journal_entry_id');
    }

    public function accountingCode(): BelongsTo
    {
        return $this->belongsTo(AccountingCode::class);
    }

    protected static function newFactory(): AccountingJournalLineFactory
    {
        return AccountingJournalLineFactory::new();
    }
}
