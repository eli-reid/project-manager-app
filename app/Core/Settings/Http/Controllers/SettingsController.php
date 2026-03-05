<?php

namespace App\Core\Settings\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * SettingsController
 * 
 * Admin controller for managing application settings.
 * Handles all admin settings management routes.
 */
class SettingsController
{
    use AuthorizesRequests;

    /**
     * Display settings management page
     */
    public function index(): View
    {
        // Authorize: can:admin check happens via middleware
        return view('core::admin.settings.index');
    }

    /**
     * Export all settings as JSON
     */
    public function export()
    {
        $settings = \App\Core\Settings\Models\SettingsSqlite::all()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                ]];
            });

        return response()->json($settings)
            ->header('Content-Disposition', 'attachment; filename=settings-' . now()->format('Y-m-d-His') . '.json');
    }

    /**
     * Import settings from JSON
     */
    public function import()
    {
        // This would typically handle file upload and validation
        return view('core::settings.admin.settings.import');
    }
}
