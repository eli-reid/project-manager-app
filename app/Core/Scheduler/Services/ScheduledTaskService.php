<?php

namespace App\Services;

use App\Models\ScheduledTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduledTaskService
{
    /**
     * Calculate the next run time for a scheduled task
     * 
     * @param ScheduledTask $task
     * @return Carbon|null Returns Carbon in UTC or null if task shouldn't run
     */
    public function calculateNextRun(ScheduledTask $task): ?Carbon
    {
        if (!$task->is_active || !$task->is_enabled) {
            return null;
        }

        // Get current time in the task's timezone
        $taskTimezone = $task->timezone ?? 'America/New_York';
        $now = now()->timezone($taskTimezone);
        
        // Handle both H:i and H:i:s time formats
        $baseTime = str_contains($task->time, ':') && substr_count($task->time, ':') === 1 
            ? Carbon::createFromFormat('H:i', $task->time, $taskTimezone)
            : Carbon::createFromFormat('H:i:s', $task->time, $taskTimezone);
        
        $nextRun = null;
        
        switch ($task->schedule_type) {
            case 'daily':
                $nextRun = $this->calculateDailyNextRun($task, $baseTime, $now);
                break;
                
            case 'weekly':
                $nextRun = $this->calculateWeeklyNextRun($task, $baseTime, $now);
                break;
                
            case 'monthly':
                $nextRun = $this->calculateMonthlyNextRun($task, $baseTime, $now);
                break;
                
            case 'yearly':
                $nextRun = $this->calculateYearlyNextRun($task, $baseTime, $now);
                break;
                
            case 'specific_date':
                $nextRun = $this->calculateSpecificDateNextRun($task, $now);
                break;
        }
        
        // Convert to UTC for storage
        return $nextRun ? $nextRun->setTimezone('UTC') : null;
    }

    /**
     * Calculate next run for a daily task
     */
    protected function calculateDailyNextRun(ScheduledTask $task, Carbon $baseTime, Carbon $now): Carbon
    {
        $taskTimezone = $task->timezone ?? 'America/New_York';
        $nextRun = $now->copy()->setTimeFromTimeString($task->time);
        
        if ($nextRun <= $now) {
            $nextRun->addDay();
        }
        
        // Apply repeat interval for daily tasks
        if ($task->repeat_frequency === 'daily' && $task->repeat_interval > 1) {
            $createdInTaskTz = $task->created_at->copy()->timezone($taskTimezone);
            $daysSinceStart = $createdInTaskTz->diffInDays($nextRun);
            $remainder = $daysSinceStart % $task->repeat_interval;
            if ($remainder !== 0) {
                $nextRun->addDays($task->repeat_interval - $remainder);
            }
        }
        
        return $nextRun;
    }

    /**
     * Calculate next run for a weekly task
     */
    protected function calculateWeeklyNextRun(ScheduledTask $task, Carbon $baseTime, Carbon $now): Carbon
    {
        if (!$task->days_of_week) {
            return $now->copy()->addWeek()->setTimeFromTimeString($task->time);
        }
        
        $daysOfWeek = collect($task->days_of_week)->sort();
        
        foreach ($daysOfWeek as $dayOfWeek) {
            $nextRun = $now->copy()->next($dayOfWeek)->setTimeFromTimeString($task->time);
            
            // If it's today and the time hasn't passed yet
            if ($now->dayOfWeek === $dayOfWeek) {
                $todayRun = $now->copy()->setTimeFromTimeString($task->time);
                if ($todayRun > $now) {
                    return $todayRun;
                }
            }
            
            if ($nextRun > $now) {
                return $nextRun;
            }
        }
        
        // If no day this week, get the first day of next week
        $firstDay = $daysOfWeek->first();
        return $now->copy()->next($firstDay)->setTimeFromTimeString($task->time);
    }

    /**
     * Calculate next run for a monthly task
     */
    protected function calculateMonthlyNextRun(ScheduledTask $task, Carbon $baseTime, Carbon $now): Carbon
    {
        $nextRun = $now->copy()->day($task->day_of_month ?? 1)->setTimeFromTimeString($task->time);
        
        if ($nextRun <= $now) {
            $nextRun->addMonth();
        }
        
        // Handle months that don't have the target day (e.g., Feb 30)
        while ($nextRun->day !== ($task->day_of_month ?? 1)) {
            $nextRun->addMonth()->day(1)->addDays(($task->day_of_month ?? 1) - 1);
        }
        
        return $nextRun;
    }

    /**
     * Calculate next run for a yearly task
     */
    protected function calculateYearlyNextRun(ScheduledTask $task, Carbon $baseTime, Carbon $now): Carbon
    {
        $nextRun = $now->copy()
            ->month($task->month ?? 1)
            ->day($task->day_of_month ?? 1)
            ->setTimeFromTimeString($task->time);
        
        if ($nextRun <= $now) {
            $nextRun->addYear();
        }
        
        return $nextRun;
    }

    /**
     * Calculate next run for a specific date task
     */
    protected function calculateSpecificDateNextRun(ScheduledTask $task, Carbon $now): ?Carbon
    {
        $taskTimezone = $task->timezone ?? 'America/New_York';
        
        if ($task->specific_date && $task->specific_date >= $now->toDateString()) {
            $nextRun = Carbon::parse($task->specific_date, $taskTimezone)
                ->setTimeFromTimeString($task->time);
                
            if ($nextRun <= $now) {
                return null;
            }
            
            return $nextRun;
        }
        
        return null;
    }

    /**
     * Generate a human-readable schedule description
     */
    public function formatScheduleDescription(ScheduledTask $task): string
    {
        $description = '';
        $taskTimezone = $task->timezone ?? 'America/New_York';
        $tzAbbr = now()->timezone($taskTimezone)->format('T');
        
        switch ($task->schedule_type) {
            case 'daily':
                $description = 'Daily';
                break;
                
            case 'weekly':
                if ($task->days_of_week) {
                    $days = collect($task->days_of_week)->map(function ($day) {
                        return Carbon::create()->dayOfWeek($day)->format('l');
                    })->join(', ');
                    $description = "Weekly on {$days}";
                } else {
                    $description = 'Weekly';
                }
                break;
                
            case 'monthly':
                if ($task->day_of_month) {
                    $description = "Monthly on day {$task->day_of_month}";
                } else {
                    $description = 'Monthly';
                }
                break;
                
            case 'yearly':
                if ($task->month && $task->day_of_month) {
                    $monthName = Carbon::create()->month($task->month)->format('F');
                    $description = "Yearly on {$monthName} {$task->day_of_month}";
                } else {
                    $description = 'Yearly';
                }
                break;
                
            case 'specific_date':
                $description = "On {$task->specific_date->format('M j, Y')}";
                break;
        }
        
        $description .= " at {$task->time} {$tzAbbr}";
        
        if ($task->repeat_frequency !== 'once') {
            $description .= " (repeats {$task->repeat_frequency}";
            if ($task->repeat_interval > 1) {
                $description .= " every {$task->repeat_interval}";
            }
            $description .= ')';
        }
        
        return $description;
    }

    /**
     * Mark a task as run and calculate next run time
     */
    public function markTaskAsRun(ScheduledTask $task): ScheduledTask
    {
        $task->update([
            'last_run_at' => now(),
            'run_count' => $task->run_count + 1,
            'next_run_at' => $this->calculateNextRun($task),
        ]);
        
        // Check if task should be deactivated
        if ($this->shouldDeactivateTask($task)) {
            $task->update(['is_active' => false]);
        }
        
        return $task;
    }

    /**
     * Determine if a task should be deactivated
     */
    public function shouldDeactivateTask(ScheduledTask $task): bool
    {
        // Deactivate if max occurrences reached
        if ($task->max_occurrences && $task->run_count >= $task->max_occurrences) {
            return true;
        }
        
        // Deactivate if repeat_until date has passed
        if ($task->repeat_until && now() > $task->repeat_until) {
            return true;
        }
        
        // Deactivate one-time tasks after they run
        if ($task->repeat_frequency === 'once') {
            return true;
        }
        
        return false;
    }

    /**
     * Toggle task enabled status
     */
    public function toggleTask(ScheduledTask $task): ScheduledTask
    {
        $task->update([
            'is_enabled' => !$task->is_enabled,
            'next_run_at' => !$task->is_enabled ? null : $this->calculateNextRun($task),
        ]);
        
        return $task;
    }
}
