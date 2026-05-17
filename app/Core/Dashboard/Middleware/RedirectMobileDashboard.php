<?php

namespace App\Core\Dashboard\Middleware;

use App\Core\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileDashboard
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

        if (! $user instanceof User || $user->isAdmin()) {
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

        $mobileRoute = $this->resolveMobileRouteName($routeName);

        if (is_string($mobileRoute) && $mobileRoute !== '') {
            return $mobileRoute;
        }

        if ($this->isDesktopUserSurface($routeName)) {
            return 'mobile.dashboard';
        }

        return null;
    }

    private function resolveMobileRouteName(string $routeName): ?string
    {
        $candidates = [
            'mobile.'.$routeName,
            Str::replaceFirst('.', '.mobile.', $routeName),
        ];

        if (Str::endsWith($routeName, '.index')) {
            $candidates[] = Str::replaceLast('.index', '.mobile.global', $routeName);
        }

        foreach ($candidates as $candidate) {
            if (Route::has($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isDesktopUserSurface(string $routeName): bool
    {
        return str_starts_with($routeName, 'dashboard')
            || str_starts_with($routeName, 'projects.')
            || str_starts_with($routeName, 'timecards.')
            || str_starts_with($routeName, 'dailies.')
            || str_starts_with($routeName, 'submittals.')
            || str_starts_with($routeName, 'stock-orders.')
            || str_starts_with($routeName, 'change-orders.')
            || str_starts_with($routeName, 'tasks.')
            || str_starts_with($routeName, 'documents.')
            || str_starts_with($routeName, 'reports.');
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
        if ($request->hasHeader('X-Livewire') || $request->hasHeader('X-Livewire-Navigate')) {
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
