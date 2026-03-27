<?php

namespace App\Domains\Stock\Models;

use App\Domains\Stock\Database\Factories\StockOrderItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOrderItem extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'stock_order_id',
        'quantity',
        'item_name',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function stockOrder(): BelongsTo
    {
        return $this->belongsTo(StockOrder::class);
    }

    protected static function newFactory(): StockOrderItemFactory
    {
        return StockOrderItemFactory::new();
    }
}
