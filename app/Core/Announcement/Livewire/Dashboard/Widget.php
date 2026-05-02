<?php

namespace App\Core\Announcement\Livewire\Dashboard;

use App\Core\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Widget extends Component
{
    public int $limit = 5;

    public function dismissAnnouncement(string $announcementId): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $announcement = Announcement::query()
            ->active()
            ->findOrFail($announcementId);

        $announcement->dismissFor($user);
    }

    public function render()
    {
        $user = Auth::user();

        return view('announcement::livewire.dashboard.widget', [
            'announcements' => Announcement::query()
                ->active()
                ->visibleTo($user)
                ->latest()
                ->limit($this->limit)
                ->get(),
        ]);
    }
}
