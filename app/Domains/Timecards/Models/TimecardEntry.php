<?php

namespace App\Domains\Timecards\Models;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Database\Factories\TimecardEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTimecardEntry
 */
class TimecardEntry extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'timecard_id',
        'user_id',
        'project_id',
        'custom_project_name',
        'date',
        'start_time',
        'hours',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'string',
            'hours' => 'float',
        ];
    }

    public function timecard(): BelongsTo
    {
        return $this->belongsTo(Timecard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected static function newFactory(): TimecardEntryFactory
    {
        return TimecardEntryFactory::new();
    }
}
