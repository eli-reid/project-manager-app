<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Domains\Documents\Models\Document;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.mobile')]
#[Title('Documents')]
class MobileGlobalIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function render()
    {
        $documentsQuery = Document::query()
            ->userOwned()
            ->global()
            ->latest();

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%');
            });
        }

        return view('documents::livewire.user.documents.mobile-global-index', [
            'documents' => $documentsQuery->get(),
        ]);
    }
}
