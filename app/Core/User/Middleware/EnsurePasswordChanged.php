<?php

namespace App\Core\User\Middleware;

use App\Core\User\Models\User;
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
        return $request->routeIs('password.*')
            || $request->routeIs('logout')
            || $request->routeIs('verification.*')
            || $request->routeIs('two-factor.*')
            || $request->is('livewire/*')
            || $request->is('logout')
            || $request->is('telescope')
            || $request->is('telescope/*')
            || $request->is('up');
    }
}
