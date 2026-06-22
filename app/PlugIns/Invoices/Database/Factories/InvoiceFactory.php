<?php

namespace App\Domains\Invoices\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = round($subtotal * 0.1, 2);

        return [
            'project_id' => Project::factory(),
            'vendor_name' => fake()->company(),
            'invoice_number' => fake()->optional()->bothify('INV-####'),
            'invoice_date' => fake()->dateTimeBetween('-60 days', 'now'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'payment_date' => null,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'status' => fake()->randomElement(array_keys(InvoiceStatusEnum::toArray())),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'verified_by' => null,
            'verified_at' => null,
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => InvoiceStatusEnum::Pending->value]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatusEnum::Verified->value,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatusEnum::Paid->value,
            'payment_date' => now()->toDateString(),
            'paid_at' => now(),
            'verified_by' => User::factory(),
            'verified_at' => now()->subDay(),
        ]);
    }
}
