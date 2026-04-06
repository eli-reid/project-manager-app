<?php

namespace App\Domains\Timecards\Models;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Database\Factories\TimecardFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperTimecard
 */
class Timecard extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'week_starting',
        'week_ending',
        'status',
        'total_hours',
        'notes',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_starting' => 'date',
            'week_ending' => 'date',
            'total_hours' => 'float',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimecardEntry::class);
    }

    protected static function newFactory(): TimecardFactory
    {
        return TimecardFactory::new();
    }
}
