<?php

namespace App\Core\PluginExternalApi\Database\Factories;

use App\Core\PluginExternalApi\Models\ExternalApiConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalApiConnection>
 */
class ExternalApiConnectionFactory extends Factory
{
    protected $model = ExternalApiConnection::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug' => $slug,
            'name' => str($slug)->replace('-', ' ')->title()->value(),
            'driver' => fake()->randomElement(['rest', 'oauth2', 'webhook']),
            'base_url' => fake()->url(),
            'auth_type' => fake()->randomElement(['token', 'oauth2', 'signature']),
            'status' => ExternalApiConnection::STATUS_STAGED,
            'trust_level' => ExternalApiConnection::TRUST_EXTERNAL_API_ONLY,
            'execution_mode' => ExternalApiConnection::EXECUTION_OUT_OF_PROCESS,
            'allowed_scopes' => ['read'],
            'metadata' => [
                'timeout_seconds' => 10,
            ],
            'last_verified_at' => null,
        ];
    }
}
