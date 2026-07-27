<?php

namespace App\Core\PluginSystem\Services;

use App\Core\PluginSystem\Models\InstalledPlugin;

class PluginInstallService
{
    public function __construct(
        private readonly PluginSecurityReviewService $pluginSecurityReviewService
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function stageMarketplacePlugin(array $manifest): InstalledPlugin
    {
        $review = $this->pluginSecurityReviewService->reviewManifest($manifest);
        $normalized = $review['normalized'];
        $recommendedSecurityStatus = $review['recommended_security_status'];

        return InstalledPlugin::query()->updateOrCreate(
            ['slug' => $normalized['slug']],
            [
                'name' => $normalized['name'],
                'provider_class' => $normalized['provider'],
                'package_name' => $normalized['package_name'],
                'version' => $normalized['version'],
                'source_type' => InstalledPlugin::SOURCE_MARKETPLACE,
                'trust_level' => InstalledPlugin::TRUST_REVIEWED_THIRD_PARTY,
                'execution_mode' => InstalledPlugin::EXECUTION_IN_PROCESS_LIMITED,
                'status' => $recommendedSecurityStatus === InstalledPlugin::SECURITY_BLOCKED
                    ? InstalledPlugin::STATUS_QUARANTINED
                    : InstalledPlugin::STATUS_STAGED,
                'security_status' => $recommendedSecurityStatus,
                'manifest_checksum' => $normalized['checksum'],
                'signature_fingerprint' => $normalized['signature'],
                'capabilities' => $normalized['capabilities'],
                'required_permissions' => $normalized['required_permissions'],
                'metadata' => [
                    ...$normalized['metadata'],
                    'security_findings' => $review['findings'],
                ],
                'installed_at' => null,
                'last_reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );
    }
}
