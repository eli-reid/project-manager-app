<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Global Documents')]
class GlobalIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public ?string $projectId = null;

    public ?Project $project = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);

        $projectId = request()->query('project_id');

        if (is_string($projectId) && $projectId !== '') {
            $project = Project::query()->findOrFail($projectId);
            $this->authorize('view', $project);

            $this->projectId = (string) $project->id;
            $this->project = $project;
        }
    }

    public function render()
    {
        $documentsQuery = Document::query()
            ->with('uploadedBy:id,first_name,last_name')
            ->latest();

        if ($this->projectId !== null) {
            $documentsQuery->ownedByProject($this->projectId);
        } else {
            $documentsQuery
                ->userOwned()
                ->global();
        }

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%');
            });
        }

        return view('documents::livewire.user.documents.global-index', [
            'documents' => $documentsQuery->get(),
            'project' => $this->project,
        ]);
    }
}
