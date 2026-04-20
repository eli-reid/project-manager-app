<?php

namespace App\Domains\Timecards\Models;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Database\Factories\TimecardRequiredUserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTimecardRequiredUser
 */
class TimecardRequiredUser extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'timecard_required_users';

    protected $fillable = [
        'user_id',
        'reminders_enabled',
        'effective_start_date',
        'effective_end_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reminders_enabled' => 'boolean',
            'effective_start_date' => 'datetime',
            'effective_end_date' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this timecard required entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function newFactory(): TimecardRequiredUserFactory
    {
        return TimecardRequiredUserFactory::new();
    }
}
