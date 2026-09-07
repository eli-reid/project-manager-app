<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSheet;

final class PlanAnnotationService
{
    public function __construct(private readonly PlanGeometryService $geometry) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $author, PlanSheet $sheet, array $attributes): PlanAnnotation
    {
        $annotation = new PlanAnnotation([
            'plan_sheet_id' => $sheet->id,
            'author_id' => $author->id,
            'type' => $attributes['type'],
            'geometry' => $this->geometry->normalize($attributes['geometry']),
            'style' => $attributes['style'] ?? [],
            'content' => $attributes['content'] ?? null,
            'plan_sheet_revision_id' => $attributes['plan_sheet_revision_id'] ?? null,
        ]);
        abort_unless($author->can('create', $annotation), 403);
        $annotation->save();

        return $annotation;
    }

    public function setStatus(User $user, PlanAnnotation $annotation, string $status): PlanAnnotation
    {
        abort_unless(in_array($status, ['open', 'resolved'], true), 422);
        abort_unless($user->can('resolve', $annotation), 403);
        $annotation->update(['status' => $status]);

        return $annotation->fresh();
    }
}
