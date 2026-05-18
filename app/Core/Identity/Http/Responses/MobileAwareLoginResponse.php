<?php

namespace App\Core\Identity\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class MobileAwareLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirectPath = $this->resolveRedirectPath($request);

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectPath);
    }

    private function resolveRedirectPath(Request $request): string
    {
        if ($this->isMobileUserAgent($request) && Route::has('mobile.dashboard')) {
            return route('mobile.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
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
