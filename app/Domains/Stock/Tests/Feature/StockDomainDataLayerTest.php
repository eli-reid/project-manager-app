<?php

use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderItem;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Support\Facades\Schema;

it('creates stock domain tables through migrations', function (): void {
    expect(Schema::hasTable('stock_orders'))->toBeTrue()
        ->and(Schema::hasTable('stock_order_items'))->toBeTrue()
        ->and(Schema::hasTable('stock_order_templates'))->toBeTrue();
});

it('creates stock orders and related items via factories', function (): void {
    $stockOrder = StockOrder::factory()->create();

    StockOrderItem::factory()->count(3)->create([
        'stock_order_id' => $stockOrder->id,
    ]);

    expect($stockOrder->fresh()->items)->toHaveCount(3)
        ->and($stockOrder->fresh()->user)->not->toBeNull();
});

it('creates stock order templates with casted template items', function (): void {
    $template = StockOrderTemplate::factory()->create([
        'is_active' => true,
    ]);

    expect($template->template_items)->toBeArray()
        ->and(StockOrderTemplate::active()->count())->toBeGreaterThan(0)
        ->and($template->createdBy)->not->toBeNull();
});
