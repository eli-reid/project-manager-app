<?php

namespace App\Http\Middleware;

use App\Core\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileRoutes
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mobileRoute = $this->mobileRedirectRoute($request);

        if ($mobileRoute === null) {
            return $next($request);
        }

        return redirect()->route($mobileRoute);
    }

    private function mobileRedirectRoute(Request $request): ?string
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        if (! $request->isMethod('get') || $request->expectsJson() || $request->ajax()) {
            return null;
        }

        if ($this->isLivewireRequest($request) || ! $this->isMobileUserAgent($request)) {
            return null;
        }

        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        if ($this->isMobileRoute($routeName) || $this->isFileRoute($routeName)) {
            return null;
        }

        $mobileRoute = $this->resolveMobileRouteNameFromRegistry($routeName);

        if (is_string($mobileRoute) && $mobileRoute !== '') {
            return $mobileRoute;
        }

        if ($this->isRegisteredDesktopSurface($routeName)) {
            return $this->fallbackRouteName();
        }

        return null;
    }

    private function resolveMobileRouteNameFromRegistry(string $routeName): ?string
    {
        $exactMappings = $this->exactRouteMappings();
        $prefixMappings = $this->prefixRouteMappings();

        if (isset($exactMappings[$routeName])) {
            return Route::has($exactMappings[$routeName]) ? $exactMappings[$routeName] : null;
        }

        foreach ($prefixMappings as $desktopPrefix => $mobilePrefix) {
            if (! str_starts_with($routeName, $desktopPrefix)) {
                continue;
            }

            $candidate = $mobilePrefix.substr($routeName, strlen($desktopPrefix));

            if (Route::has($candidate)) {
                return $candidate;
            }

            return null;
        }

        return null;
    }

    private function isRegisteredDesktopSurface(string $routeName): bool
    {
        if (array_key_exists($routeName, $this->exactRouteMappings())) {
            return true;
        }

        foreach (array_keys($this->prefixRouteMappings()) as $desktopPrefix) {
            if (str_starts_with($routeName, $desktopPrefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function exactRouteMappings(): array
    {
        $mappings = config('mobile_redirect.exact', []);

        return is_array($mappings) ? $mappings : [];
    }

    /**
     * @return array<string, string>
     */
    private function prefixRouteMappings(): array
    {
        $mappings = config('mobile_redirect.prefix', []);

        return is_array($mappings) ? $mappings : [];
    }

    private function fallbackRouteName(): string
    {
        $fallbackRoute = config('mobile_redirect.fallback', 'mobile.dashboard');

        return is_string($fallbackRoute) && $fallbackRoute !== ''
            ? $fallbackRoute
            : 'mobile.dashboard';
    }

    private function isMobileRoute(string $routeName): bool
    {
        return str_starts_with($routeName, 'mobile.') || str_contains($routeName, '.mobile.');
    }

    private function isFileRoute(string $routeName): bool
    {
        return str_ends_with($routeName, '.download') || str_ends_with($routeName, '.view');
    }

    private function isLivewireRequest(Request $request): bool
    {
        // Keep component/message requests out of redirect logic, but allow
        // Livewire SPA page navigations (X-Livewire-Navigate) to be redirected.
        if ($request->hasHeader('X-Livewire')) {
            return true;
        }

        return $request->routeIs('livewire.*')
            || $request->is('livewire/*')
            || $request->is('livewire-*/*');
    }

    private function isMobileUserAgent(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        if ($userAgent === '') {
            return false;
        }

        return str_contains($userAgent, 'android')
            || str_contains($userAgent, 'iphone')
            || str_contains($userAgent, 'ipad')
            || str_contains($userAgent, 'ipod')
            || str_contains($userAgent, 'mobile')
            || str_contains($userAgent, 'opera mini')
            || str_contains($userAgent, 'iemobile');
    }
}
