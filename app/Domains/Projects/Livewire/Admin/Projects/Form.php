<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Project Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public ?string $project_number = null;

    public ?string $description = null;

    public string $status = 'pending';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $client_id = null;

    public ?string $address_id = null;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('create', Project::class);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_number' => ['nullable', 'string', 'max:255', 'unique:projects,project_number'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(ProjectStatusEnum::toArray()))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'address_id' => ['nullable', 'exists:addresses,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', Project::class);

        Project::query()->create($this->validate());

        session()->flash('success', 'Project created successfully.');

        $this->redirectRoute('admin.projects.index', navigate: true);
    }

    #[On('client-inline-created')]
    public function setClientFromWidget(string $clientId): void
    {
        $this->client_id = $clientId;
        $this->address_id = null;
    }

    #[On('address-inline-created')]
    public function setAddressFromWidget(string $addressId): void
    {
        $this->address_id = $addressId;
    }

    public function render()
    {
        $clientId = $this->client_id;

        return view('projects::livewire.admin.projects.form', [
            'statuses' => ProjectStatusEnum::toArray(),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'addresses' => Address::query()
                ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
                ->orderBy('address1')
                ->get(['id', 'address1', 'city', 'state', 'client_id']),
        ]);
    }
}
