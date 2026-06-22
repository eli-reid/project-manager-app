<?php

namespace App\Domains\Accounting\Livewire\Admin\AccountingCodes;

use App\Domains\Accounting\Models\AccountingCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Accounting Code Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?AccountingCode $accountingCode = null;

    public bool $isEdit = false;

    public string $code = '';

    public string $name = '';

    public ?string $description = null;

    public bool $is_active = true;

    public function mount(?AccountingCode $accountingCode = null): void
    {
        if ($accountingCode !== null && $accountingCode->exists) {
            $this->authorize('update', $accountingCode);

            $this->accountingCode = $accountingCode;
            $this->isEdit = true;
            $this->code = $accountingCode->code;
            $this->name = $accountingCode->name;
            $this->description = $accountingCode->description;
            $this->is_active = (bool) $accountingCode->is_active;

            return;
        }

        $this->authorize('create', AccountingCode::class);
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('accounting_codes', 'code')->ignore($this->accountingCode?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        if ($this->description === '') {
            $this->description = null;
        }

        $validated = $this->validate();

        if ($this->isEdit) {
            $accountingCode = $this->accountingCode;
            if ($accountingCode === null) {
                return;
            }

            $this->authorize('update', $accountingCode);
            $accountingCode->update($validated);

            session()->flash('success', 'Accounting code updated successfully.');
            $this->redirectRoute('admin.accounting-codes.index', navigate: true);

            return;
        }

        $this->authorize('create', AccountingCode::class);

        AccountingCode::query()->create($validated);

        session()->flash('success', 'Accounting code created successfully.');
        $this->redirectRoute('admin.accounting-codes.index', navigate: true);
    }

    public function render()
    {
        return view('accounting::livewire.admin.accounting-codes.form');
    }
}
