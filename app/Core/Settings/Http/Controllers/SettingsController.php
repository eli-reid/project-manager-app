<?php

namespace App\Core\Settings\Http\Controllers;

use App\Core\Settings\Models\SettingsSqlite;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

/**
 * SettingsController
 *
 * Admin controller for managing application settings.
 * Handles all admin settings management routes.
 */
class SettingsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display settings management page
     */
    public function index(): View
    {
        $this->authorize('viewAny', SettingsSqlite::class);

        return view('core::admin.settings.index');
    }

    /**
     * Import settings from JSON
     */
    public function import()
    {
        $this->authorize('import', SettingsSqlite::class);

        return view('core::admin.settings.import');
    }
}
