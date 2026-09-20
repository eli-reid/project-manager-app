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
            'visibility' => $attributes['visibility'] ?? PlanAnnotation::VISIBILITY_PUBLIC,
            'plan_sheet_revision_id' => $attributes['plan_sheet_revision_id'] ?? null,
        ]);
        abort_unless($author->can('create', $annotation), 403);
        $annotation->save();

        return $annotation;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, PlanAnnotation $annotation, array $attributes): PlanAnnotation
    {
        abort_unless($user->can('update', $annotation), 403);

        if (array_key_exists('visibility', $attributes) && $attributes['visibility'] !== null) {
            abort_unless(
                in_array($attributes['visibility'], [PlanAnnotation::VISIBILITY_PUBLIC, PlanAnnotation::VISIBILITY_PRIVATE], true),
                422
            );
        }

        $annotation->fill(array_filter([
            'content' => $attributes['content'] ?? null,
            'visibility' => $attributes['visibility'] ?? null,
        ], fn (mixed $value): bool => $value !== null));
        $annotation->save();

        return $annotation->fresh();
    }

    public function delete(User $user, PlanAnnotation $annotation): void
    {
        abort_unless($user->can('delete', $annotation), 403);
        $annotation->delete();
    }

    public function setStatus(User $user, PlanAnnotation $annotation, string $status): PlanAnnotation
    {
        abort_unless(in_array($status, ['open', 'resolved'], true), 422);
        abort_unless($user->can('resolve', $annotation), 403);
        $annotation->update(['status' => $status]);

        return $annotation->fresh();
    }
}
