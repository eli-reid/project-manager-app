<?php

namespace App\Domains\Documents\Livewire\PublicShares;

use App\Domains\Documents\Models\DocumentShare;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public-share')]
class Show extends Component
{
    public DocumentShare $share;

    public string $token = '';

    public function mount(string $token): void
    {
        $share = DocumentShare::query()
            ->where('share_token', $token)
            ->with('document', 'createdBy')
            ->firstOrFail();

        if (! $share->isValid()) {
            abort(403, $share->getExpirationReason());
        }

        $this->share = $share;
        $this->token = $token;
    }

    public function render()
    {
        return view('documents::livewire.public-shares.show', [
            'share' => $this->share,
        ])->title($this->share->document->title.' - Shared Document');
    }
}
