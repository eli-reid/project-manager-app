<?php

namespace App\Core\Announcement\Http\Controllers\Admin;

use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Announcement\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Core\Announcement\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Core\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::query()
            ->with('creator:id,first_name,last_name')
            ->latest()
            ->paginate(10);

        return view('announcement::admin.announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('announcement::admin.announcements.create', [
            'types' => AnnouncementType::options(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Announcement::query()->create([
            ...$validated,
            'created_by' => (string) $request->user()->id,
            'is_active' => $request->boolean('is_active', true),
            'is_dismissable' => $request->boolean('is_dismissable', false),
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('announcement::admin.announcements.edit', [
            'announcement' => $announcement,
            'types' => AnnouncementType::options(),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validated();

        $announcement->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'is_dismissable' => $request->boolean('is_dismissable', false),
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
