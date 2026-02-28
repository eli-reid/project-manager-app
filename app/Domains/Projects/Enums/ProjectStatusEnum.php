<?php

namespace App\Domains\Projects\Enums;

enum ProjectStatusEnum: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case PLANNING = 'planning';
    case ESTIMATING = 'estimating';
    case APPROVED = 'approved';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case FINAL_INSPECTION = 'final_inspection';
    case CANCELLED = 'cancelled';
    case ARCHIVED = 'archived';


    /**
     * Get status display name
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            self::PLANNING => 'Planning',
            self::ESTIMATING => 'Estimating',
            self::APPROVED => 'Approved',
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::FINAL_INSPECTION => 'Final Inspection',
            self::CANCELLED => 'Cancelled',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get status color CSS class
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::PENDING => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::PLANNING => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            self::ESTIMATING => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::APPROVED => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
            self::SCHEDULED => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
            self::IN_PROGRESS => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::ON_HOLD => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::FINAL_INSPECTION => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
            self::CANCELLED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::ARCHIVED => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Check if status is inactive (completed or cancelled)
     */
    public function isInactive(): bool
    {
        return in_array($this, [self::COMPLETED, self::FINAL_INSPECTION, self::CANCELLED]);
    }

    /**
     * Get all statuses as an associative array for dropdowns
     */
    public static function toArray(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::PLANNING->value => 'Planning',
            self::ESTIMATING->value => 'Estimating',
            self::APPROVED->value => 'Approved',
            self::SCHEDULED->value => 'Scheduled',
            self::IN_PROGRESS->value => 'In Progress',
            self::ON_HOLD->value => 'On Hold',
            self::COMPLETED->value => 'Completed',
            self::FINAL_INSPECTION->value => 'Final Inspection',
            self::CANCELLED->value => 'Cancelled',
            self::ARCHIVED->value => 'Archived',
        ];
    }
}
