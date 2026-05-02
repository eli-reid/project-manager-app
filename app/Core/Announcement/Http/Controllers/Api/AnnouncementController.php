<?php

namespace App\Core\Announcement\Http\Controllers\Api;

use App\Core\Announcement\Http\Resources\AnnouncementResource;
use App\Core\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::query()
            ->active()
            ->visibleTo($request->user())
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'content', 'type', 'is_dismissable', 'start_date', 'end_date', 'created_at']);

        return response()->json([
            'data' => AnnouncementResource::collection($announcements)->resolve(),
        ]);
    }
}
