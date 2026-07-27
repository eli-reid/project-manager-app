<?php

namespace App\Core\PluginSandbox\Services;

use App\Core\PluginSandbox\Models\SandboxProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class SandboxProfileRegistryService
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function stageProfile(array $definition): SandboxProfile
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($definition, [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'name' => ['required', 'string', 'max:120'],
            'isolation_driver' => ['required', 'string', 'max:60'],
            'applies_to_trust_levels' => ['sometimes', 'array'],
            'applies_to_trust_levels.*' => ['string', 'max:80'],
            'allowed_host_apis' => ['sometimes', 'array'],
            'allowed_host_apis.*' => ['string', 'max:120'],
            'resource_limits' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ])->validate();

        return SandboxProfile::query()->updateOrCreate(
            ['slug' => $validated['slug']],
            [
                'name' => $validated['name'],
                'isolation_driver' => $validated['isolation_driver'],
                'status' => SandboxProfile::STATUS_DRAFT,
                'applies_to_trust_levels' => Arr::wrap($validated['applies_to_trust_levels'] ?? []),
                'allowed_host_apis' => Arr::wrap($validated['allowed_host_apis'] ?? []),
                'resource_limits' => $validated['resource_limits'] ?? [],
                'metadata' => $validated['metadata'] ?? [],
                'last_verified_at' => null,
            ]
        );
    }
}
