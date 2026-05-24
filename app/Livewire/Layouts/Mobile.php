<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class Mobile extends Component
{
    public ?string $title = null;

    public string $mobileDashboardFallbackUrl;

    public function mount(?string $title = null): void
    {
        $this->title = $title;
        $this->mobileDashboardFallbackUrl = route('mobile.dashboard');
    }

    public function render(): View
    {
        return view('livewire.layouts.mobile', [
            'title' => $this->title,
            'mobileDashboardFallbackUrl' => $this->mobileDashboardFallbackUrl,
        ]);
    }
}
