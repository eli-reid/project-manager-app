<?php

namespace App\Core\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * SettingsController
 *
 * Admin controller for managing application settings.
 * Handles all admin settings management routes.
 */
class SettingsController extends Controller
{
    /**
     * Display settings management page
     */
    public function index(): View
    {
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
            ->header('Content-Disposition', 'attachment; filename=settings-'.now()->format('Y-m-d-His').'.json');
    }

    /**
     * Import settings from JSON
     */
    public function import()
    {
        return view('core::admin.settings.import');
    }
}
