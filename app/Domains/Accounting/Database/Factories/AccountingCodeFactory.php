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
        return [
            'code' => fake()->unique()->bothify('ACCT-####'),
            'name' => fake()->words(3, true),
            'account_type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense', 'other']),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
