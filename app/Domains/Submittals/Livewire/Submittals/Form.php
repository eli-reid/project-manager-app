<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Submittal Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Submittal $submittal = null;

    public string $projectId = '';

    public string $type = '';

    public string $specReference = '';

    public string $vendor = '';

    public ?string $needByDate = null;

    public function mount(?Submittal $submittal = null): void
    {
        $this->submittal = $submittal;

        if ($submittal instanceof Submittal) {
            $this->authorize('update', $submittal);
            $this->projectId = (string) $submittal->project_id;
            $this->type = (string) $submittal->type;
            $this->specReference = (string) ($submittal->spec_reference ?? '');
            $this->vendor = (string) ($submittal->vendor ?? '');
            $this->needByDate = $submittal->need_by_date?->format('Y-m-d');

            return;
        }

        $this->authorize('create', Submittal::class);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'projectId' => ['required', 'string', 'exists:projects,id'],
            'type' => ['required', 'string', 'max:120'],
            'specReference' => ['nullable', 'string', 'max:120'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'needByDate' => ['nullable', 'date'],
        ]);

        $payload = [
            'project_id' => $validated['projectId'],
            'type' => $validated['type'],
            'spec_reference' => $validated['specReference'] ?: null,
            'vendor' => $validated['vendor'] ?: null,
            'need_by_date' => $validated['needByDate'],
        ];

        if ($this->submittal instanceof Submittal) {
            $this->submittal->update($payload);
            session()->flash('success', 'Submittal updated successfully.');
            $this->redirectRoute('submittals.show', $this->submittal);

            return;
        }

        $created = Submittal::query()->create([
            ...$payload,
            'status' => Submittal::STATUS_DRAFT,
            'submitted_by_id' => (string) Auth::id(),
        ]);

        session()->flash('success', 'Submittal created successfully.');
        $this->redirectRoute('submittals.show', $created);
    }

    public function render()
    {
        return view('submittals::livewire.user.submittals.form', [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_number']),
        ]);
    }
}
