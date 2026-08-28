<?php

namespace App\Http\Middleware;
class LoadSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, $next)
    {
        // Preload all settings to avoid multiple database queries during the request lifecycle
        app(\App\Core\Settings\Services\SettingsSqliteService::class)->preloadAllSettings();

        return $next($request);
    }
}