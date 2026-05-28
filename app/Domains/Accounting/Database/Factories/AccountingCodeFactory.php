<?php

namespace App\Domains\Accounting\Database\Factories;

use App\Domains\Accounting\Models\AccountingCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingCode>
 */
class AccountingCodeFactory extends Factory
{
    protected $model = AccountingCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountType = fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense', 'other']);

        return [
            'code' => fake()->unique()->bothify('ACCT-####'),
            'name' => fake()->words(3, true),
            'account_type' => $accountType,
            'normal_balance' => in_array($accountType, ['asset', 'expense'], true) ? 'debit' : 'credit',
            'parent_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
