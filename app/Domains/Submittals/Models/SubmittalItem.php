<?php

namespace App\Domains\Submittals\Models;

use App\Domains\Submittals\Database\Factories\SubmittalItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmittalItem extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submittal_id',
        'description',
        'manufacturer',
        'model',
        'part_number',
        'quantity',
        'unit',
        'status',
        'comments',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }

    protected static function newFactory(): SubmittalItemFactory
    {
        return SubmittalItemFactory::new();
    }
}
