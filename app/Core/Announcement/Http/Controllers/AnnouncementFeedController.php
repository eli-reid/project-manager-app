<?php

namespace App\Core\Announcement\Http\Controllers;

use App\Core\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AnnouncementFeedController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::query()
            ->active()
            ->with('creator:id,first_name,last_name')
            ->latest()
            ->limit(20)
            ->get();

        return view('announcement::announcements.index', [
            'announcements' => $announcements,
        ]);
    }
}
