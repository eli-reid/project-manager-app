<?php

namespace App\Domains\RFIs\Models;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Database\Factories\RFIFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperRFI
 */
class RFI extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'rfis';

    protected $fillable = [
        'project_id',
        'number',
        'subject',
        'body',
        'status',
        'requested_by_id',
        'answered_by_id',
        'answer',
        'due_date',
        'answered_at',
        'cost_impact',
        'schedule_impact_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'answered_at' => 'datetime',
            'cost_impact' => 'decimal:2',
            'schedule_impact_days' => 'integer',
            'number' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_id');
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_ANSWERED,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ];
    }

    protected static function newFactory(): RFIFactory
    {
        return RFIFactory::new();
    }
}
