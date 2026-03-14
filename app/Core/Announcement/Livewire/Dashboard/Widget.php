<?php

namespace App\Core\Announcement\Livewire\Dashboard;

use App\Core\Announcement\Models\Announcement;
use Livewire\Component;

class Widget extends Component
{
    public int $limit = 5;

    public function render()
    {
        return view('announcement::livewire.dashboard.widget', [
            'announcements' => Announcement::query()
                ->active()
                ->latest()
                ->limit($this->limit)
                ->get(),
        ]);
    }
}
