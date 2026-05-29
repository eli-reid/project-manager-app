<?php

namespace App\Domains\RFIs\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectTab extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * @var Collection<int, RFI>
     */
    public $rfis;

    public int $rfiCount = 0;

    public bool $isCreateMode = false;

    // Form fields
    public string $subject = '';

    public string $body = '';

    public ?string $dueDate = null;

    public function mount(Project $project, $rfis = null, int $rfiCount = 0, bool $isCreateMode = false): void
    {
        $this->project = $project;
        $this->rfis = $rfis ?? collect();
        $this->rfiCount = $rfiCount;
        $this->isCreateMode = $isCreateMode;
    }

    public function submitRfi(RFILifecycleService $service): void
    {
        $this->authorize('create', RFI::class);

        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'dueDate' => ['nullable', 'date'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $service->create($this->project, $user, [
            'subject' => $this->subject,
            'body' => $this->body,
            'due_date' => $this->dueDate,
        ]);

        $this->reset(['subject', 'body', 'dueDate']);
        $this->isCreateMode = false;

        // Reload list
        $this->rfis = RFI::query()
            ->with(['requestedBy:id,first_name,last_name'])
            ->where('project_id', $this->project->id)
            ->latest()
            ->limit(20)
            ->get();

        $this->rfiCount = RFI::query()
            ->where('project_id', $this->project->id)
            ->count();
    }

    public function cancelCreate(): void
    {
        $this->reset(['subject', 'body', 'dueDate']);
        $this->isCreateMode = false;
    }

    public function render()
    {
        return view('rfis::livewire.admin.projects.project-tab');
    }
}
