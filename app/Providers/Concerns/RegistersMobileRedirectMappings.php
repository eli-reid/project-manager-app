<?php

namespace App\Providers\Concerns;

trait RegistersMobileRedirectMappings
{
    protected function registerMobileRoutePrefixMapping(string $desktopPrefix, string $mobilePrefix): void
    {
        $mappings = config('mobile_redirect.prefix', []);

        if (! is_array($mappings)) {
            $mappings = [];
        }

        $mappings[$desktopPrefix] = $mobilePrefix;

        config(['mobile_redirect.prefix' => $mappings]);
    }

    protected function registerMobileExactRouteMapping(string $desktopRouteName, string $mobileRouteName): void
    {
        $mappings = config('mobile_redirect.exact', []);

        if (! is_array($mappings)) {
            $mappings = [];
        }

        $mappings[$desktopRouteName] = $mobileRouteName;

        config(['mobile_redirect.exact' => $mappings]);
    }
}
