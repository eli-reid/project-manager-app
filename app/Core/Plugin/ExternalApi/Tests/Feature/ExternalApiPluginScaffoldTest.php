<?php

use App\Core\PluginExternalApi\Models\ExternalApiConnection;
use App\Core\PluginExternalApi\Services\ExternalApiRegistryService;

it('stages external api connections as out-of-process integrations', function (): void {
    $connection = app(ExternalApiRegistryService::class)->stageConnection([
        'slug' => 'acme-external-api',
        'name' => 'ACME External API',
        'driver' => 'rest',
        'base_url' => 'https://api.example.test',
        'auth_type' => 'token',
        'allowed_scopes' => ['projects:read', 'documents:read'],
        'metadata' => [
            'retry_profile' => 'standard',
        ],
    ]);

    expect($connection->status)->toBe(ExternalApiConnection::STATUS_STAGED)
        ->and($connection->trust_level)->toBe(ExternalApiConnection::TRUST_EXTERNAL_API_ONLY)
        ->and($connection->execution_mode)->toBe(ExternalApiConnection::EXECUTION_OUT_OF_PROCESS)
        ->and($connection->allowed_scopes)->toBe(['projects:read', 'documents:read']);
});
