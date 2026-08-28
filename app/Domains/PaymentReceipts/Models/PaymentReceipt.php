<?php

namespace App\Domains\PaymentReceipts\Models;

use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Database\Factories\PaymentReceiptFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'project_payment_receipts';

    protected $fillable = [
        'project_id',
        'received_on',
        'amount',
        'received_from',
        'reference',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function newFactory(): PaymentReceiptFactory
    {
        return PaymentReceiptFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
