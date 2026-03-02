<?php
namespace App\Core\Scheduler\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScheduledTask extends Model
{
    protected $table = 'scheduled_tasks';

    protected $fillable = [
        'feature_type',
        'payload',
        'schedule_type',
        'cron_expression',
        'day_of_week',
        'day_of_month',
        'run_time',
        'run_at',
        'timezone',
        'last_run_at',
        'next_run_at',
        'is_active',
        'is_enabled',
    ];

    protected $casts = [
        'payload'      => 'array',
        'run_at'       => 'datetime',
        'last_run_at'  => 'datetime',
        'next_run_at'  => 'datetime',
        'is_active'    => 'boolean',
        'is_enabled'   => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRunnable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('is_enabled', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now('UTC'));
    }

    /*
    |--------------------------------------------------------------------------
    | Scheduling Logic
    |--------------------------------------------------------------------------
    */

    public function markAsRun(): void
    {
        $this->last_run_at = now('UTC');
        $this->next_run_at = $this->computeNextRun();
        $this->save();
    }

    public function computeNextRun(): ?Carbon
    {
        $tz = $this->timezone ?? 'UTC';

        return match ($this->schedule_type) {
            'once'   => $this->computeOnce($tz),
            'daily'  => $this->computeDaily($tz),
            'weekly' => $this->computeWeekly($tz),
            'monthly'=> $this->computeMonthly($tz),
            'cron'   => $this->computeCron($tz),
            default  => null,
        };
    }

    protected function computeOnce(string $tz): ?Carbon
    {
        if (!$this->run_at) {
            return null;
        }

        $run = $this->run_at->copy()->setTimezone($tz);

        return $run->isPast()
            ? null
            : $run->clone()->setTimezone('UTC');
    }

    protected function computeDaily(string $tz): Carbon
    {
        $next = Carbon::now($tz)->setTimeFromTimeString($this->run_time);

        if ($next->isPast()) {
            $next->addDay();
        }

        return $next->clone()->setTimezone('UTC');
    }

    protected function computeWeekly(string $tz): Carbon
    {
        $next = Carbon::now($tz)
            ->next($this->day_of_week)
            ->setTimeFromTimeString($this->run_time);

        if ($next->isPast()) {
            $next->addWeek();
        }

        return $next->clone()->setTimezone('UTC');
    }

    protected function computeMonthly(string $tz): Carbon
    {
        $next = Carbon::now($tz)
            ->setDay($this->day_of_month)
            ->setTimeFromTimeString($this->run_time);

        if ($next->isPast()) {
            $next->addMonth();
        }

        return $next->clone()->setTimezone('UTC');
    }

    protected function computeCron(string $tz): ?Carbon
    {
        if (!$this->cron_expression) {
            return null;
        }

       $cron = new \Cron\CronExpression($this->cron_expression);

        $next = Carbon::instance($cron->getNextRunDate('now', 0, false, $tz));

        return $next->clone()->setTimezone('UTC');
    }
}

