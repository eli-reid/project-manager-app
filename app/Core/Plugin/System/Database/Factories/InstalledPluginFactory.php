<?php

namespace App\Core\PluginSystem\Database\Factories;

use App\Core\PluginSystem\Models\InstalledPlugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstalledPlugin>
 */
class InstalledPluginFactory extends Factory
{
    protected $model = InstalledPlugin::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);
        $version = fake()->numberBetween(1, 3).'.'.fake()->numberBetween(0, 9).'.'.fake()->numberBetween(0, 9);

        return [
            'slug' => $slug,
            'name' => str($slug)->replace('-', ' ')->title()->value(),
            'provider_class' => 'Vendor\\'.str($slug)->studly()->value().'\\Providers\\'.str($slug)->studly()->value().'ServiceProvider',
            'package_name' => 'vendor/'.$slug,
            'version' => $version,
            'source_type' => InstalledPlugin::SOURCE_MARKETPLACE,
            'trust_level' => InstalledPlugin::TRUST_REVIEWED_THIRD_PARTY,
            'execution_mode' => InstalledPlugin::EXECUTION_IN_PROCESS_LIMITED,
            'status' => InstalledPlugin::STATUS_STAGED,
            'security_status' => InstalledPlugin::SECURITY_PENDING_REVIEW,
            'manifest_checksum' => fake()->regexify('[a-f0-9]{64}'),
            'signature_fingerprint' => Str::upper(fake()->regexify('[a-f0-9]{40}')),
            'capabilities' => ['settings'],
            'required_permissions' => ['plugins.view'],
            'metadata' => [
                'source_url' => fake()->url(),
            ],
            'installed_at' => null,
            'last_reviewed_at' => null,
            'reviewed_by' => null,
        ];
    }
}
