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
     * Import settings from JSON
     */
    public function import()
    {
        return view('core::admin.settings.import');
    }
}
