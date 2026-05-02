<?php

namespace App\Core\Announcement\Http\Controllers;

use App\Core\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementFeedController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::query()
            ->active()
            ->visibleTo($request->user())
            ->withCreator()
            ->latest()
            ->limit(20)
            ->get();

        return view('announcement::announcements.index', [
            'announcements' => $announcements,
        ]);
    }
}
