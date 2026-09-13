<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DetectPlanLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $revisionId) {}

    public function handle(): void
    {
        $revision = PlanSheetRevision::query()->with(['sheet.project', 'sheet', 'links'])->findOrFail($this->revisionId);
        $text = (string) $revision->text_layer;
        $pattern = '/'.trim((string) config('plans.sheet_number_pattern'), '/').'/i';
        preg_match_all($pattern, $text, $matches);

        $targets = PlanSheet::query()
            ->where('project_id', $revision->sheet->project_id)
            ->whereIn('sheet_number', array_map('strtoupper', array_unique($matches[0] ?? [])))
            ->get()
            ->keyBy(fn (PlanSheet $sheet): string => strtoupper((string) $sheet->sheet_number));

        foreach (array_unique($matches[0] ?? []) as $candidate) {
            $target = $targets->get(strtoupper($candidate));
            if (! $target instanceof PlanSheet || $target->is($revision->sheet)) {
                continue;
            }

            $revision->links()->firstOrCreate(
                ['target_sheet_id' => $target->id, 'auto_detected' => true],
                ['hotspot' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1], 'label' => 'Detected '.$target->sheet_number],
            );
        }
    }
}
