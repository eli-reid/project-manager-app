<?php

namespace App\Domains\Stock\Livewire\User\Templates;

use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Order from Template')]
class FromTemplate extends Component
{
    use AuthorizesRequests;

    public StockOrderTemplate $template;

    public ?string $project_id = null;

    public string $urgency = StockOrder::URGENCY_MEDIUM;

    public ?string $po_number = null;

    public ?string $notes = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(StockOrderTemplate $stockOrderTemplate): void
    {
        $this->authorize('view', $stockOrderTemplate);
        $this->authorize('create', StockOrder::class);

        $this->template = $stockOrderTemplate;
        $this->urgency = $stockOrderTemplate->urgency;
        $this->notes = $stockOrderTemplate->notes;

        $this->items = collect($stockOrderTemplate->template_items ?? [])->map(fn (array $item) => [
            'item_name' => $item['item_name'] ?? '',
            'quantity' => (int) ($item['quantity'] ?? 1),
            'notes' => '',
        ])->toArray();

        if (empty($this->items)) {
            $this->items = [['item_name' => '', 'quantity' => 1, 'notes' => '']];
        }
    }

    protected function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'urgency' => ['required', 'in:low,medium,high'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = ['item_name' => '', 'quantity' => 1, 'notes' => ''];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    public function submit(): void
    {
        $this->authorize('create', StockOrder::class);
        $this->authorize('view', $this->template);

        $validated = $this->validate();

        $order = StockOrder::query()->create([
            'user_id' => Auth::id(),
            'project_id' => $validated['project_id'],
            'urgency' => $validated['urgency'],
            'po_number' => $validated['po_number'],
            'notes' => $validated['notes'],
            'status' => StockOrder::STATUS_PENDING,
        ]);

        foreach ($validated['items'] as $item) {
            $order->items()->create($item);
        }

        session()->flash('success', 'Stock order submitted from template successfully.');
        $this->redirectRoute('stock-orders.show', ['stockOrder' => $order], navigate: true);
    }

    public function render()
    {
        return view('stock::livewire.user.templates.from-template', [
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'project_number']),
            'urgencies' => [
                StockOrder::URGENCY_LOW => 'Low',
                StockOrder::URGENCY_MEDIUM => 'Medium',
                StockOrder::URGENCY_HIGH => 'High',
            ],
        ]);
    }
}
