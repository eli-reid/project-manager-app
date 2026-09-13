<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Sheets;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Compare extends Component
{
    use AuthorizesRequests;

    public PlanSheet $sheet;

    public string $leftRevisionId = '';

    public string $rightRevisionId = '';

    public string $mode = 'side-by-side';

    public bool $locked = true;

    public function mount(PlanSheet $sheet): void
    {
        $this->sheet = $sheet->load(['revisions.set']);
        $this->authorize('compare', $sheet);
        $revisions = $this->sheet->revisions->sortByDesc('created_at')->values();
        abort_unless($revisions->count() >= 2, 404);
        $this->leftRevisionId = $revisions->get(1)->id;
        $this->rightRevisionId = $revisions->get(0)->id;
    }

    public function selectMode(string $mode): void
    {
        abort_unless(in_array($mode, ['side-by-side', 'overlay', 'difference'], true), 422);
        $this->mode = $mode;
    }

    public function render()
    {
        $left = $this->sheet->revisions->firstWhere('id', $this->leftRevisionId);
        $right = $this->sheet->revisions->firstWhere('id', $this->rightRevisionId);
        abort_unless($left instanceof PlanSheetRevision && $right instanceof PlanSheetRevision, 404);

        return view('plans::livewire.sheets.compare', [
            'revisions' => $this->sheet->revisions->sortByDesc('created_at'),
            'left' => $left,
            'right' => $right,
        ]);
    }
}
