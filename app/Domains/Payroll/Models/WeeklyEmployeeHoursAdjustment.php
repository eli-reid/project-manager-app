<?php

namespace App\Domains\Payroll\Models;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Database\Factories\WeeklyEmployeeHoursAdjustmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperWeeklyEmployeeHoursAdjustment
 */
class WeeklyEmployeeHoursAdjustment extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'week_start',
        'user_id',
        'source_hours',
        'adjusted_hours',
        'reason',
        'edited_by_id',
        'edited_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'source_hours' => 'float',
            'adjusted_hours' => 'float',
            'edited_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by_id');
    }

    protected static function newFactory(): WeeklyEmployeeHoursAdjustmentFactory
    {
        return WeeklyEmployeeHoursAdjustmentFactory::new();
    }
}
