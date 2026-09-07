<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Jobs\SplitPlanSetJob;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class PlanSetIngestionService
{
    public function __construct(private readonly AssetOrchestratorContract $assets) {}

    public function ingest(Project $project, User $actor, UploadedFile $file, array $attributes = []): PlanSet
    {
        return DB::transaction(function () use ($project, $actor, $file, $attributes): PlanSet {
            $planSet = PlanSet::query()->create([
                'project_id' => $project->id,
                'name' => $attributes['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'discipline' => $attributes['discipline'] ?? null,
                'issued_at' => $attributes['issued_at'] ?? null,
                'uploaded_by_id' => $actor->id,
            ]);
            $asset = $this->assets->upload(
                $actor,
                $file,
                new AssetReferenceTarget('plans', $planSet->id, 'source'),
            );
            $planSet->update(['source_asset_id' => $asset->id]);
            SplitPlanSetJob::dispatch($planSet->id);

            return $planSet->fresh();
        });
    }
}
