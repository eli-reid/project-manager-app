<?php

namespace App\Domains\Stock\Livewire\User\StockOrders;

use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Order')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?StockOrder $stockOrder = null;

    public bool $isEdit = false;

    public ?string $project_id = null;

    public string $urgency = StockOrder::URGENCY_MEDIUM;

    public ?string $po_number = null;

    public ?string $notes = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(?StockOrder $stockOrder = null): void
    {
        if ($stockOrder !== null && $stockOrder->exists) {
            $this->authorize('update', $stockOrder);

            $this->stockOrder = $stockOrder;
            $this->isEdit = true;
            $this->project_id = $stockOrder->project_id;
            $this->urgency = $stockOrder->urgency;
            $this->po_number = $stockOrder->po_number;
            $this->notes = $stockOrder->notes;
            $this->items = $stockOrder->items->map(fn (StockOrderItem $item) => [
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'notes' => $item->notes ?? '',
            ])->toArray();

            return;
        }

        $this->authorize('create', StockOrder::class);

        $this->items = [
            ['item_name' => '', 'quantity' => 1, 'notes' => ''],
        ];
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

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            if ($this->stockOrder === null) {
                return;
            }

            $this->authorize('update', $this->stockOrder);

            $this->stockOrder->update([
                'project_id' => $validated['project_id'],
                'urgency' => $validated['urgency'],
                'po_number' => $validated['po_number'],
                'notes' => $validated['notes'],
            ]);

            $this->stockOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                $this->stockOrder->items()->create($item);
            }

            session()->flash('success', 'Stock order updated successfully.');
            $this->redirectToOrder($this->stockOrder);

            return;
        }

        $this->authorize('create', StockOrder::class);

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

        session()->flash('success', 'Stock order submitted successfully.');
        $this->redirectToOrder($order);
    }

    protected function redirectToOrder(StockOrder $stockOrder): void
    {
        $this->redirectRoute('stock-orders.show', ['stockOrder' => $stockOrder], navigate: true);
    }

    public function render()
    {
        return view('stock::livewire.user.stock-orders.form', [
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'project_number']),
            'urgencies' => [
                StockOrder::URGENCY_LOW => 'Low',
                StockOrder::URGENCY_MEDIUM => 'Medium',
                StockOrder::URGENCY_HIGH => 'High',
            ],
        ]);
    }
}
