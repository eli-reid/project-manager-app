<?php

namespace App\Domains\PaymentReceipts\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReceipt>
 */
class PaymentReceiptFactory extends Factory
{
    protected $model = PaymentReceipt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'received_on' => fake()->dateTimeBetween('-90 days', 'now'),
            'amount' => fake()->randomFloat(2, 100, 25000),
            'received_from' => fake()->company(),
            'reference' => fake()->optional()->bothify('PAY-####'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
