<?php

namespace App\Core\Cpanel\Database\Factories;

use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CachedEmailAccount>
 */
class CachedEmailAccountFactory extends Factory
{
    protected $model = CachedEmailAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->userName().'@example.test',
            'domain' => 'example.test',
            'suspended' => false,
            'quota' => 250,
            'usage' => fake()->numberBetween(0, 250),
            'usage_percentage' => fake()->randomFloat(2, 0, 100),
            'raw_data' => [],
            'user_id' => User::factory(),
            'last_synced_at' => now(),
            'sync_failed' => false,
            'sync_error' => null,
        ];
    }
}
