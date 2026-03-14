<?php

namespace App\Core\Announcement\Http\Controllers\Api;

use App\Core\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::query()
            ->active()
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'content', 'type', 'is_dismissable', 'start_date', 'end_date', 'created_at']);

        return response()->json([
            'data' => $announcements,
        ]);
    }
}
