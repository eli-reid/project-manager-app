<?php

namespace App\Core\Identity\Middleware;

use App\Core\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->password_change_required || $this->shouldPassThrough($request)) {
            return $next($request);
        }

        if ($request->isMethod('get') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()
            ->route('password.change')
            ->with('status', 'Please change your password before continuing.');
    }

    private function shouldPassThrough(Request $request): bool
    {
        if ($this->isLivewireRequest($request)) {
            return true;
        }

        return $request->routeIs('password.*')
            || $request->routeIs('logout')
            || $request->routeIs('verification.*')
            || $request->routeIs('two-factor.*')
            || $request->is('logout')
            || $request->is('telescope')
            || $request->is('telescope/*')
            || $request->is('up');
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
}
