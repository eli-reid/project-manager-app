<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRateTypes;

use App\Domains\Payroll\Models\PayRateType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('payroll::layouts.payroll-admin')]
#[Title('Payroll Rate Types')]
class Index extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'active';

    // Modal state
    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?string $editingId = null;

    public ?string $deletingId = null;

    public bool $isSystemType = false;

    // Form fields
    public string $formName = '';

    public string $formKey = '';

    public string $formDescription = '';

    public bool $formIsActive = true;

    public int $formSortOrder = 0;

    public function mount(): void
    {
        $this->authorize('payroll-rates.view');
    }

    public function updatedSearch(): void
    {
        // Full-page component; reset hooks can be added when pagination is introduced.
    }

    public function updatedStatusFilter(): void
    {
        // Full-page component; reset hooks can be added when pagination is introduced.
    }

    public function updatedFormName(): void
    {
        if ($this->editingId === null) {
            $this->formKey = Str::of($this->formName)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('payroll-rates.manage');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEdit(string $id): void
    {
        $this->authorize('payroll-rates.manage');

        $type = PayRateType::findOrFail($id);

        $this->editingId = $id;
        $this->isSystemType = $type->is_system;
        $this->formName = $type->name;
        $this->formKey = $type->key;
        $this->formDescription = $type->description ?? '';
        $this->formIsActive = $type->is_active;
        $this->formSortOrder = $type->sort_order;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->authorize('payroll-rates.manage');

        $validated = $this->validate(
            [
                'formName' => ['required', 'string', 'max:100'],
                'formKey' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[a-z][a-z0-9_]*$/',
                    $this->editingId !== null
                        ? Rule::unique('pay_rate_types', 'key')->ignore($this->editingId)
                        : Rule::unique('pay_rate_types', 'key'),
                ],
                'formDescription' => ['nullable', 'string', 'max:500'],
                'formIsActive' => ['boolean'],
                'formSortOrder' => ['integer', 'min:0', 'max:9999'],
            ],
            [
                'formName.required' => 'A name is required.',
                'formKey.required' => 'A key is required.',
                'formKey.regex' => 'The key may only contain lowercase letters, numbers, and underscores, starting with a letter.',
                'formKey.unique' => 'This key is already in use.',
            ],
        );

        $data = [
            'name' => $validated['formName'],
            'description' => ($validated['formDescription'] ?? '') !== '' ? $validated['formDescription'] : null,
            'is_active' => $validated['formIsActive'],
            'sort_order' => $validated['formSortOrder'],
        ];

        if ($this->editingId !== null) {
            $type = PayRateType::findOrFail($this->editingId);
            if (! $type->is_system) {
                $data['key'] = $validated['formKey'];
            }
            $type->update($data);
        } else {
            $data['key'] = $validated['formKey'];
            PayRateType::create($data);
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(string $id): void
    {
        $this->authorize('payroll-rates.manage');

        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteType(): void
    {
        $this->authorize('payroll-rates.manage');

        if ($this->deletingId === null) {
            return;
        }

        $type = PayRateType::findOrFail($this->deletingId);

        if ($type->payRates()->exists()) {
            $this->addError('delete', 'This rate type has associated employee rates and cannot be deleted.');
            $this->showDeleteModal = false;
            $this->deletingId = null;

            return;
        }

        $type->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render(): View
    {
        $search = trim($this->search);
        $statusFilter = $this->statusFilter;

        $types = PayRateType::query()
            ->withCount('payRates')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('key', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('payroll::livewire.admin.pay-rate-types.index', [
            'types' => $types,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->isSystemType = false;
        $this->formName = '';
        $this->formKey = '';
        $this->formDescription = '';
        $this->formIsActive = true;
        $this->formSortOrder = 0;
        $this->resetValidation();
    }
}
