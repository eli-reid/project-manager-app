<?php

namespace App\Domains\Stock\Livewire\Admin\Templates;

use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Stock Order Template Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?StockOrderTemplate $stockOrderTemplate = null;

    public bool $isEdit = false;

    public string $name = '';

    public ?string $description = null;

    public string $urgency = StockOrderTemplate::URGENCY_MEDIUM;

    public ?string $notes = null;

    public bool $is_global = false;

    public bool $is_active = true;

    /** @var array<int, array<string, mixed>> */
    public array $templateItems = [];

    public function mount(?StockOrderTemplate $stockOrderTemplate = null): void
    {
        if ($stockOrderTemplate !== null && $stockOrderTemplate->exists) {
            $this->authorize('update', $stockOrderTemplate);

            $this->stockOrderTemplate = $stockOrderTemplate;
            $this->isEdit = true;
            $this->name = $stockOrderTemplate->name;
            $this->description = $stockOrderTemplate->description;
            $this->urgency = $stockOrderTemplate->urgency;
            $this->notes = $stockOrderTemplate->notes;
            $this->is_global = $stockOrderTemplate->is_global;
            $this->is_active = $stockOrderTemplate->is_active;
            $this->templateItems = $stockOrderTemplate->template_items ?? [];

            return;
        }

        $this->authorize('create', StockOrderTemplate::class);
    }

    public function addItem(): void
    {
        $this->templateItems[] = ['item_name' => '', 'quantity' => 1, 'notes' => ''];
    }

    public function removeItem(int $index): void
    {
        array_splice($this->templateItems, $index, 1);
        $this->templateItems = array_values($this->templateItems);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'urgency' => ['required', 'in:low,medium,high'],
            'notes' => ['nullable', 'string'],
            'is_global' => ['boolean'],
            'is_active' => ['boolean'],
            'templateItems' => ['array'],
            'templateItems.*.item_name' => ['required', 'string', 'max:255'],
            'templateItems.*.quantity' => ['required', 'integer', 'min:1'],
            'templateItems.*.notes' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'urgency' => $validated['urgency'],
            'notes' => $validated['notes'],
            'is_global' => $validated['is_global'],
            'is_active' => $validated['is_active'],
            'template_items' => $validated['templateItems'],
        ];

        if ($this->isEdit) {
            if ($this->stockOrderTemplate === null) {
                return;
            }

            $this->authorize('update', $this->stockOrderTemplate);
            $this->stockOrderTemplate->update($data);

            session()->flash('success', 'Template updated successfully.');
            $this->redirectRoute('admin.stock-order-templates.index', navigate: true);

            return;
        }

        $this->authorize('create', StockOrderTemplate::class);

        StockOrderTemplate::query()->create([
            ...$data,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Template created successfully.');
        $this->redirectRoute('admin.stock-order-templates.index', navigate: true);
    }

    public function render()
    {
        return view('stock::livewire.admin.templates.form', [
            'urgencies' => StockOrder::urgencies(),
        ]);
    }
}
