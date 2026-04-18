<?php

namespace App\Core\Scheduler\Models;

use App\Core\Identity\Models\User;
use App\Core\Scheduler\Services\ScheduledTaskService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperScheduledTask
 */
class ScheduledTask extends Model
{
    use HasUlids;

    protected $table = 'scheduled_tasks';

    protected $fillable = [
        'name',
        'description',
        'available_task_id',
        'schedule_type',
        'time',
        'timezone',
        'days_of_week',
        'day_of_month',
        'month',
        'specific_date',
        'repeat_frequency',
        'repeat_interval',
        'repeat_until',
        'max_occurrences',
        'is_active',
        'is_enabled',
        'last_run_at',
        'next_run_at',
        'run_count',
        'task_config',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'task_config' => 'array',
        'specific_date' => 'date',
        'repeat_until' => 'date',
        'is_active' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_enabled' => true,
        'run_count' => 0,
        'repeat_interval' => 1,
        'repeat_frequency' => 'once',
        'timezone' => 'America/New_York',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function availableTask(): BelongsTo
    {
        return $this->belongsTo(AvailableTask::class, 'available_task_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeRunnable($query)
    {
        return $query->active()->enabled();
    }

    public function scopeDue($query)
    {
        $nowUtc = now('UTC')->format('Y-m-d H:i:s');

        return $query->runnable()
            ->where('next_run_at', '<=', $nowUtc)
            ->whereNotNull('next_run_at');
    }

    public function getNextRunAtAttribute($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value, 'UTC');
    }

    public function setNextRunAtAttribute($value): void
    {
        if ($value instanceof Carbon) {
            $this->attributes['next_run_at'] = $value->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');

            return;
        }

        $this->attributes['next_run_at'] = $value;
    }

    public function getLastRunAtAttribute($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value, 'UTC');
    }

    public function setLastRunAtAttribute($value): void
    {
        if ($value instanceof Carbon) {
            $this->attributes['last_run_at'] = $value->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');

            return;
        }

        $this->attributes['last_run_at'] = $value;
    }

    public function calculateNextRun(): ?Carbon
    {
        return app(ScheduledTaskService::class)->calculateNextRun($this);
    }

    public function markAsRun(): self
    {
        app(ScheduledTaskService::class)->markTaskAsRun($this);

        return $this;
    }
}
