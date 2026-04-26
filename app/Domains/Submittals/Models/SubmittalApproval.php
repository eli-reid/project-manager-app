<?php

namespace App\Domains\Submittals\Models;

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Database\Factories\SubmittalApprovalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmittalApproval extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submittal_id',
        'step',
        'reviewer_id',
        'status',
        'reviewed_at',
        'comments',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    protected static function newFactory(): SubmittalApprovalFactory
    {
        return SubmittalApprovalFactory::new();
    }
}
