<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Services\PlanSheetMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FinalizePlanSetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $planSetId) {}

    public function handle(PlanSheetMatcher $matcher): void
    {
        $set = PlanSet::query()->find($this->planSetId);
        if ($set === null) {
            return;
        }
        $set->revisions()->each(function ($revision) use ($matcher): void {
            $sheet = $matcher->match($revision);
            $sheet->revisions()->where('id', '!=', $revision->id)->update(['is_current' => false]);
            $revision->update(['is_current' => true, 'published_at' => now()]);
            $sheet->update(['current_revision_id' => $revision->id]);
        });
        $set->update(['status' => PlanSet::STATUS_READY]);
    }
}
