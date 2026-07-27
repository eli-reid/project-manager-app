<?php

namespace App\Core\PluginSandbox\Database\Factories;

use App\Core\PluginSandbox\Models\SandboxProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SandboxProfile>
 */
class SandboxProfileFactory extends Factory
{
    protected $model = SandboxProfile::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug' => $slug,
            'name' => str($slug)->replace('-', ' ')->title()->value(),
            'isolation_driver' => fake()->randomElement([
                SandboxProfile::DRIVER_IN_PROCESS_GUARDED,
                SandboxProfile::DRIVER_QUEUE_WORKER,
                SandboxProfile::DRIVER_HTTP_BROKER,
            ]),
            'status' => SandboxProfile::STATUS_DRAFT,
            'applies_to_trust_levels' => ['reviewed_third_party'],
            'allowed_host_apis' => ['navigation.register', 'data.request'],
            'resource_limits' => [
                'timeout_seconds' => 5,
                'max_memory_mb' => 64,
            ],
            'metadata' => [
                'audited' => false,
            ],
            'last_verified_at' => null,
        ];
    }
}
