<?php

use App\Core\PluginSandbox\Models\SandboxProfile;
use App\Core\PluginSandbox\Services\SandboxProfileRegistryService;

it('stages sandbox profiles for plugin isolation rules', function (): void {
    $profile = app(SandboxProfileRegistryService::class)->stageProfile([
        'slug' => 'reviewed-plugin-default',
        'name' => 'Reviewed Plugin Default',
        'isolation_driver' => SandboxProfile::DRIVER_HTTP_BROKER,
        'applies_to_trust_levels' => ['reviewed_third_party', 'external_api_only'],
        'allowed_host_apis' => ['navigation.register', 'data.request'],
        'resource_limits' => [
            'timeout_seconds' => 10,
            'max_memory_mb' => 128,
        ],
        'metadata' => [
            'requires_audit_log' => true,
        ],
    ]);

    expect($profile->status)->toBe(SandboxProfile::STATUS_DRAFT)
        ->and($profile->isolation_driver)->toBe(SandboxProfile::DRIVER_HTTP_BROKER)
        ->and($profile->applies_to_trust_levels)->toBe(['reviewed_third_party', 'external_api_only'])
        ->and($profile->allowed_host_apis)->toBe(['navigation.register', 'data.request']);
});
