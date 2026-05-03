<?php

namespace App\Core\Dashboard\Middleware;

use App\Core\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileDashboard
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirectToMobileDashboard($request)) {
            return $next($request);
        }

        return redirect()->route('mobile.dashboard');
    }

    private function shouldRedirectToMobileDashboard(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        if (! $request->isMethod('get') || $request->expectsJson() || $request->ajax()) {
            return false;
        }

        if (! $request->routeIs('dashboard')) {
            return false;
        }

        if ($this->isLivewireRequest($request)) {
            return false;
        }

        return $this->isMobileUserAgent($request);
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
