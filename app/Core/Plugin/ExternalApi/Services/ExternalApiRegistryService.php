<?php

namespace App\Core\PluginExternalApi\Services;

use App\Core\PluginExternalApi\Models\ExternalApiConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class ExternalApiRegistryService
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function stageConnection(array $definition): ExternalApiConnection
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($definition, [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', 'string', 'max:60'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'auth_type' => ['nullable', 'string', 'max:60'],
            'allowed_scopes' => ['sometimes', 'array'],
            'allowed_scopes.*' => ['string', 'max:80'],
            'metadata' => ['sometimes', 'array'],
        ])->validate();

        return ExternalApiConnection::query()->updateOrCreate(
            ['slug' => $validated['slug']],
            [
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'base_url' => $validated['base_url'] ?? null,
                'auth_type' => $validated['auth_type'] ?? null,
                'status' => ExternalApiConnection::STATUS_STAGED,
                'trust_level' => ExternalApiConnection::TRUST_EXTERNAL_API_ONLY,
                'execution_mode' => ExternalApiConnection::EXECUTION_OUT_OF_PROCESS,
                'allowed_scopes' => Arr::wrap($validated['allowed_scopes'] ?? []),
                'metadata' => $validated['metadata'] ?? [],
                'last_verified_at' => null,
            ]
        );
    }
}
