<?php

namespace App\Core\Announcement\Livewire\User\Announcements;

use App\Core\Announcement\Models\Announcement;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Announcements')]
class Index extends Component
{
    public function render()
    {
        $announcements = Announcement::query()
            ->active()
            ->visibleTo(request()->user())
            ->withCreator()
            ->latest()
            ->limit(20)
            ->get();

        $layout = request()->routeIs('mobile.*') ? 'layouts.mobile' : 'layouts.app';

        return view('announcement::livewire.user.announcements.index', [
            'announcements' => $announcements,
        ])->layout($layout, [
            'title' => __('Announcements'),
        ]);
    }
}
