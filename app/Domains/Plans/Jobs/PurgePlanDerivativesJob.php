<?php

declare(strict_types=1);

namespace App\Domains\Plans\Jobs;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Models\PlanSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

final class PurgePlanDerivativesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $planSetId, public string $projectId) {}

    public function handle(): void
    {
        $diskName = Settings::get('plans.storage_disk', 'local')->toString();
        $disk = Storage::disk($diskName);
        $directory = "plans/{$this->projectId}/{$this->planSetId}";

        if ($disk->exists($directory)) {
            $disk->deleteDirectory($directory);
        }

        PlanSheet::query()
            ->where('project_id', $this->projectId)
            ->doesntHave('revisions')
            ->forceDelete();
    }
}
