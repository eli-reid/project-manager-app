<?php

namespace App\Domains\Invoices\Models;

use App\Core\User\Models\User;
use App\Domains\Invoices\Database\Factories\InvoiceFactory;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperInvoice
 */
class Invoice extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'project_id',
        'vendor_name',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'verified_by',
        'verified_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatusEnum::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatusEnum::Draft;
    }

    public function isPending(): bool
    {
        return $this->status === InvoiceStatusEnum::Pending;
    }

    public function isVerified(): bool
    {
        return $this->status === InvoiceStatusEnum::Verified;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatusEnum::Paid;
    }

    public function isRejected(): bool
    {
        return $this->status === InvoiceStatusEnum::Rejected;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->isPaid()
            && ! $this->isRejected();
    }
}
