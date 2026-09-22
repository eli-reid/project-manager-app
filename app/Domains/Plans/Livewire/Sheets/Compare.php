<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Sheets;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Database\Eloquent\Collection;
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
        $this->sheet = $sheet->load([
            'revisions' => fn ($query) => $query->withPlanViewData(),
        ]);
        $this->authorize('compare', $sheet);
        $revisions = $this->orderedRevisions();
        abort_unless($revisions->count() >= 2, 404);
        $this->leftRevisionId = $revisions->get(1)->id;
        $this->rightRevisionId = $revisions->get(0)->id;
    }

    public function selectMode(string $mode): void
    {
        abort_unless(in_array($mode, ['side-by-side', 'overlay', 'difference'], true), 422);
        $this->mode = $mode;
    }

    /**
     * Revisions ordered newest-first by the date printed on the drawing set (the
     * plan's issue date), falling back to upload time when a set has no issue date.
     *
     * @return Collection<int, PlanSheetRevision>
     */
    private function orderedRevisions(): Collection
    {
        return $this->sheet->revisions
            ->sortByDesc(fn (PlanSheetRevision $revision): int => $revision->effectiveDate()->timestamp)
            ->values();
    }

    public function render()
    {
        $left = $this->sheet->revisions->firstWhere('id', $this->leftRevisionId);
        $right = $this->sheet->revisions->firstWhere('id', $this->rightRevisionId);
        abort_unless($left instanceof PlanSheetRevision && $right instanceof PlanSheetRevision, 404);

        return view('plans::livewire.sheets.compare', [
            'revisions' => $this->orderedRevisions(),
            'left' => $left,
            'right' => $right,
        ]);
    }
}
