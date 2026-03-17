<?php

namespace App\Core\Announcement\Livewire\Admin\Announcements;

use App\Core\Announcement\Models\Announcement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Announcements')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
    }

    public function deleteAnnouncement(string $announcementId): void
    {
        $announcement = Announcement::query()->findOrFail($announcementId);
        $this->authorize('delete', $announcement);

        $announcement->delete();

        session()->flash('success', 'Announcement deleted successfully.');
    }

    public function render()
    {
        return view('announcement::livewire.admin.announcements.index', [
            'announcements' => Announcement::query()
                ->with('creator:id,first_name,last_name')
                ->latest()
                ->paginate(10),
        ]);
    }
}
