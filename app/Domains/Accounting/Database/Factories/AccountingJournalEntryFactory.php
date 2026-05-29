<?php

namespace App\Domains\Accounting\Database\Factories;

use App\Domains\Accounting\Models\AccountingJournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingJournalEntry>
 */
class AccountingJournalEntryFactory extends Factory
{
    protected $model = AccountingJournalEntry::class;

    public function definition(): array
    {
        return [
            'entry_number' => 'JE-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'description' => fake()->sentence(4),
            'source_type' => null,
            'source_id' => null,
            'posted_at' => now(),
        ];
    }
}
