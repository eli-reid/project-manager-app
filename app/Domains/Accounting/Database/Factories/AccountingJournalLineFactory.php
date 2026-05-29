<?php

namespace App\Domains\Accounting\Database\Factories;

use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Accounting\Models\AccountingJournalEntry;
use App\Domains\Accounting\Models\AccountingJournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingJournalLine>
 */
class AccountingJournalLineFactory extends Factory
{
    protected $model = AccountingJournalLine::class;

    public function definition(): array
    {
        return [
            'accounting_journal_entry_id' => AccountingJournalEntry::factory(),
            'accounting_code_id' => AccountingCode::factory(),
            'line_number' => 1,
            'description' => fake()->sentence(3),
            'debit_amount' => '125.00',
            'credit_amount' => '0.00',
        ];
    }
}
