<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Sheets;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Models\PlanViewState;
use App\Domains\Plans\Services\PlanAnnotationService;
use App\Domains\Plans\Services\PlanRevisionService;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Livewire\Component;

#[Layout('layouts.app')]
class Viewer extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public PlanSheet $sheet;

    public string $activeRevisionId = '';

    public float $zoom = 1.0;

    public float $centerX = 0.5;

    public float $centerY = 0.5;

    public bool $editingMetadata = false;

    public string $metaSheetNumber = '';

    public string $metaTitle = '';

    public string $metaDiscipline = '';

    public string $notesScope = 'current';

    public bool $showPublicNotes = true;

    public bool $showPrivateNotes = true;

    public bool $showResolvedNotes = false;

    /**
     * Lightweight JSON-serializable payload of the annotations visible on the active
     * revision, kept in sync with `$wire` so the client-side canvas can redraw
     * without a full page reload after every markup change.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $annotationMarkers = [];

    public function mount(Project $project, PlanSheet $sheet): void
    {
        abort_unless($sheet->project_id === $project->id, 404);
        $this->project = $project;
        $this->sheet = $sheet->load(['currentRevision', 'revisions.set']);
        $this->authorize('view', $sheet);

        $currentRevision = $this->sheet->currentRevision ?: $this->orderedRevisions->first();
        abort_unless($currentRevision instanceof PlanSheetRevision, 404);
        $this->activeRevisionId = $currentRevision->id;

        $state = PlanViewState::query()
            ->where('user_id', Auth::id())
            ->where('plan_sheet_id', $sheet->id)
            ->first();

        if ($state instanceof PlanViewState) {
            $this->zoom = (float) $state->zoom;
            $this->centerX = (float) $state->center_x;
            $this->centerY = (float) $state->center_y;
        }

        $this->refreshAnnotationMarkers();
    }

    public function selectRevision(string $revisionId): void
    {
        abort_unless($this->sheet->revisions->contains('id', $revisionId), 404);
        $this->activeRevisionId = $revisionId;
        $this->refreshAnnotationMarkers();
    }

    public function publishRevision(string $revisionId, PlanRevisionService $revisions): void
    {
        $this->authorize('publishRevision', $this->sheet);
        $revision = $this->sheet->revisions->firstWhere('id', $revisionId);
        abort_unless($revision instanceof PlanSheetRevision, 404);
        $revisions->publish($this->sheet, $revision);
        $this->sheet->refresh()->load(['currentRevision', 'revisions.set']);
    }

    #[Renderless]
    public function persistViewState(float $zoom, float $centerX, float $centerY): void
    {
        $this->zoom = max(0.25, min(6, $zoom));
        $this->centerX = max(0, min(1, $centerX));
        $this->centerY = max(0, min(1, $centerY));

        PlanViewState::query()->updateOrCreate(
            ['user_id' => Auth::id(), 'plan_sheet_id' => $this->sheet->id],
            ['zoom' => $this->zoom, 'center_x' => $this->centerX, 'center_y' => $this->centerY, 'last_viewed_at' => now()],
        );
    }

    public function openMetadataEditor(): void
    {
        $this->authorize('update', $this->sheet);
        $this->metaSheetNumber = (string) $this->sheet->sheet_number;
        $this->metaTitle = (string) $this->sheet->title;
        $this->metaDiscipline = (string) $this->sheet->discipline;
        $this->editingMetadata = true;
    }

    public function saveMetadata(): void
    {
        $this->authorize('update', $this->sheet);

        $data = $this->validate([
            'metaSheetNumber' => [
                'nullable', 'string', 'max:50',
                Rule::unique('plan_sheets', 'sheet_number')->where('project_id', $this->project->id)->ignore($this->sheet->id),
            ],
            'metaTitle' => ['nullable', 'string', 'max:255'],
            'metaDiscipline' => ['nullable', 'string', 'max:100'],
        ]);

        $this->sheet->update([
            'sheet_number' => $data['metaSheetNumber'] !== '' ? $data['metaSheetNumber'] : null,
            'title' => $data['metaTitle'] !== '' ? $data['metaTitle'] : null,
            'discipline' => $data['metaDiscipline'] !== '' ? $data['metaDiscipline'] : null,
        ]);

        $this->sheet->refresh();
        $this->editingMetadata = false;
        session()->flash('success', 'Sheet details updated.');
    }

    public function setNotesScope(string $scope): void
    {
        abort_unless(in_array($scope, ['current', 'all'], true), 422);
        $this->notesScope = $scope;
    }

    /**
     * Keep the client-synced marker payload in step with the note filter
     * checkboxes, which are bound with `wire:model.live` and otherwise wouldn't
     * trigger a marker refresh on their own.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['showPublicNotes', 'showPrivateNotes', 'showResolvedNotes'], true)) {
            $this->refreshAnnotationMarkers();
        }
    }

    /**
     * @param  array<string, mixed>  $geometry
     */
    public function createAnnotation(string $type, array $geometry, ?string $content, string $visibility, bool $pinToRevision, PlanAnnotationService $annotations): void
    {
        abort_unless(in_array($type, ['pin', 'rect'], true), 422);
        abort_unless(in_array($visibility, [PlanAnnotation::VISIBILITY_PUBLIC, PlanAnnotation::VISIBILITY_PRIVATE], true), 422);

        /** @var User $user */
        $user = Auth::user();

        $annotations->create($user, $this->sheet, [
            'type' => $type,
            'geometry' => $geometry,
            'content' => $content !== null && trim($content) !== '' ? trim($content) : null,
            'visibility' => $visibility,
            'plan_sheet_revision_id' => $pinToRevision ? $this->activeRevisionId : null,
        ]);

        $this->refreshAnnotationMarkers();
    }

    public function updateAnnotation(string $annotationId, ?string $content, ?string $visibility, PlanAnnotationService $annotations): void
    {
        if ($visibility !== null) {
            abort_unless(in_array($visibility, [PlanAnnotation::VISIBILITY_PUBLIC, PlanAnnotation::VISIBILITY_PRIVATE], true), 422);
        }

        $annotation = PlanAnnotation::query()->whereBelongsTo($this->sheet, 'sheet')->findOrFail($annotationId);

        $annotations->update(Auth::user(), $annotation, [
            'content' => $content !== null ? trim($content) : null,
            'visibility' => $visibility,
        ]);

        $this->refreshAnnotationMarkers();
    }

    public function deleteAnnotation(string $annotationId, PlanAnnotationService $annotations): void
    {
        $annotation = PlanAnnotation::query()->whereBelongsTo($this->sheet, 'sheet')->findOrFail($annotationId);
        $annotations->delete(Auth::user(), $annotation);
        $this->refreshAnnotationMarkers();
    }

    public function toggleAnnotationStatus(string $annotationId, PlanAnnotationService $annotations): void
    {
        $annotation = PlanAnnotation::query()->whereBelongsTo($this->sheet, 'sheet')->findOrFail($annotationId);
        $annotations->setStatus(Auth::user(), $annotation, $annotation->status === 'open' ? 'resolved' : 'open');
        $this->refreshAnnotationMarkers();
    }

    /**
     * @return Collection<int, PlanSheet>
     */
    #[Computed]
    public function siblings(): Collection
    {
        return PlanSheet::query()
            ->where('project_id', $this->project->id)
            ->select(['id', 'sheet_number', 'title', 'sort_index'])
            ->orderBy('sort_index')
            ->limit(240)
            ->get();
    }

    #[Computed]
    public function canEditMetadata(): bool
    {
        return Auth::user()?->can('update', $this->sheet) ?? false;
    }

    #[Computed]
    public function canAnnotate(): bool
    {
        return Auth::user()?->hasPermission('plans.annotate') ?? false;
    }

    #[Computed]
    public function canManageAnnotations(): bool
    {
        return Auth::user()?->hasPermission('plans.manage-annotations') ?? false;
    }

    /**
     * Revisions for this sheet ordered newest-first by the date printed on the
     * drawing set (falling back to upload time when a set has no issue date),
     * rather than by upload order.
     *
     * @return Collection<int, PlanSheetRevision>
     */
    #[Computed]
    public function orderedRevisions(): Collection
    {
        return $this->sheet->revisions
            ->sortByDesc(fn (PlanSheetRevision $revision): int => $revision->effectiveDate()->timestamp)
            ->values();
    }

    /**
     * Visible annotations that apply to the active revision: sheet-following notes
     * (no revision pin) plus notes pinned specifically to this revision.
     *
     * @return Collection<int, PlanAnnotation>
     */
    #[Computed]
    public function currentRevisionAnnotations(): Collection
    {
        return $this->annotationsQuery()
            ->where(fn ($query) => $query->whereNull('plan_sheet_revision_id')->orWhere('plan_sheet_revision_id', $this->activeRevisionId))
            ->get();
    }

    /**
     * Notes pinned to other, non-active revisions - only fetched when the user asks
     * to browse notes across the whole revision history.
     *
     * @return Collection<int, PlanAnnotation>
     */
    #[Computed]
    public function historicalAnnotations(): Collection
    {
        if ($this->notesScope !== 'all') {
            return new Collection;
        }

        return $this->annotationsQuery()
            ->whereNotNull('plan_sheet_revision_id')
            ->where('plan_sheet_revision_id', '!=', $this->activeRevisionId)
            ->with('revision.set')
            ->get()
            ->sortByDesc(fn (PlanAnnotation $annotation): int => $annotation->revision?->effectiveDate()->timestamp ?? 0)
            ->values();
    }

    private function annotationsQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return PlanAnnotation::query()
            ->whereBelongsTo($this->sheet, 'sheet')
            ->visibleTo($user)
            ->when(! $this->showPublicNotes, fn ($query) => $query->where('visibility', '!=', PlanAnnotation::VISIBILITY_PUBLIC))
            ->when(! $this->showPrivateNotes, fn ($query) => $query->where('visibility', '!=', PlanAnnotation::VISIBILITY_PRIVATE))
            ->when(! $this->showResolvedNotes, fn ($query) => $query->where('status', '!=', 'resolved'))
            ->with('author:id,name')
            ->latest();
    }

    private function refreshAnnotationMarkers(): void
    {
        $this->annotationMarkers = $this->currentRevisionAnnotations
            ->map(fn (PlanAnnotation $annotation): array => [
                'id' => $annotation->id,
                'type' => $annotation->type,
                'geometry' => $annotation->geometry,
                'style' => $annotation->style,
                'content' => $annotation->content,
                'visibility' => $annotation->visibility,
                'status' => $annotation->status,
                'author' => $annotation->author?->name ?? 'Unknown',
                'mine' => $annotation->author_id === Auth::id(),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        // Livewire only preserves the properties it snapshots between requests, not
        // relations eager-loaded during mount(); reload them here so every request
        // (not just the first) has `revisions.set` available without lazy-loading.
        $this->sheet->loadMissing(['currentRevision', 'revisions.set']);

        $revision = $this->sheet->revisions->firstWhere('id', $this->activeRevisionId);
        abort_unless($revision instanceof PlanSheetRevision, 404);

        return view('plans::livewire.sheets.viewer', [
            'revision' => $revision,
        ]);
    }
}
