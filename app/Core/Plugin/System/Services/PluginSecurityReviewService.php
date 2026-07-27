<?php

namespace App\Core\PluginSystem\Services;

use App\Core\PluginSystem\Models\InstalledPlugin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PluginSecurityReviewService
{
    /**
     * @param  array<string, mixed>  $manifest
     * @return array{normalized: array<string, mixed>, findings: array<int, array<string, string>>, recommended_security_status: string}
     */
    public function reviewManifest(array $manifest): array
    {
        $normalized = $this->validateManifest($manifest);
        $findings = [];

        if (! Str::contains((string) $normalized['provider'], '\\Providers\\')) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'Provider class must resolve through a dedicated Providers namespace.',
            ];
        }

        foreach ($normalized['required_permissions'] as $permission) {
            if (Str::contains((string) $permission, '*')) {
                $findings[] = [
                    'severity' => 'critical',
                    'message' => 'Wildcard permissions are not allowed for marketplace plugins.',
                ];
            }
        }

        foreach ($normalized['capabilities'] as $capability) {
            if (collect(['runtime-code-eval', 'self-update'])->contains($capability)) {
                $findings[] = [
                    'severity' => 'critical',
                    'message' => 'Manifest requests a capability that requires manual security review before install.',
                ];
            }
        }

        if ($normalized['checksum'] === '' || Str::length((string) $normalized['checksum']) < 64) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'A strong package checksum is required before staging a plugin.',
            ];
        }

        if ($normalized['signature'] === '') {
            $findings[] = [
                'severity' => 'high',
                'message' => 'A publisher signature fingerprint is required before staging a plugin.',
            ];
        }

        $recommendedSecurityStatus = collect($findings)->contains(fn (array $finding): bool => $finding['severity'] === 'critical')
            ? InstalledPlugin::SECURITY_BLOCKED
            : InstalledPlugin::SECURITY_PENDING_REVIEW;

        return [
            'normalized' => $normalized,
            'findings' => $findings,
            'recommended_security_status' => $recommendedSecurityStatus,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function validateManifest(array $manifest): array
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($manifest, [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'name' => ['required', 'string', 'max:120'],
            'package_name' => ['required', 'string', 'max:160'],
            'provider' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:40'],
            'checksum' => ['required', 'string', 'max:255'],
            'signature' => ['required', 'string', 'max:255'],
            'capabilities' => ['sometimes', 'array'],
            'capabilities.*' => ['string', 'max:80'],
            'required_permissions' => ['sometimes', 'array'],
            'required_permissions.*' => ['string', 'max:120'],
            'metadata' => ['sometimes', 'array'],
        ])->validate();

        return [
            ...$validated,
            'capabilities' => Arr::wrap($validated['capabilities'] ?? []),
            'required_permissions' => Arr::wrap($validated['required_permissions'] ?? []),
            'metadata' => $validated['metadata'] ?? [],
        ];
    }
}
