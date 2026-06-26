<?php

namespace App\Domains\Stock\Livewire\User\Templates;

use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Order Templates')]
class Browse extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'search')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', StockOrderTemplate::class);
    }

    public function updatingSearch(): void
    {
        // Triggered automatically via wire:model.live
    }

    public function render()
    {
        $userId = (string) Auth::id();

        $query = StockOrderTemplate::query()
            ->active()
            ->availableToUser($userId)
            ->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        return view('stock::livewire.user.templates.browse', [
            'templates' => $query->get(),
        ]);
    }
}
