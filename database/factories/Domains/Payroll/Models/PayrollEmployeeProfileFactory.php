<?php

namespace Database\Factories\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollEmployeeProfile>
 */
class PayrollEmployeeProfileFactory extends Factory
{
    protected $model = PayrollEmployeeProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_number' => 'EMP-'.fake()->unique()->numerify('####'),
            'ssn_encrypted' => fake()->numerify('#########'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'hire_date' => fake()->dateTimeBetween('-10 years', 'now'),
            'termination_date' => null,
            'status' => 'active',
            'pay_type' => 'hourly',
            'department' => fake()->optional()->randomElement(['Field', 'Office', 'Shop']),
            'job_classification' => fake()->randomElement(['Laborer', 'Foreman', 'Operator']),
            'union_code' => fake()->optional()->bothify('UN-###'),
            'direct_deposit_active' => fake()->boolean(),
        ];
    }
}
