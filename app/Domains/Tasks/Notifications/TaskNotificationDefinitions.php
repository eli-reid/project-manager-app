<?php

namespace App\Domains\Tasks\Notifications;

class TaskNotificationDefinitions
{
    public const ASSIGNED = 'tasks.assigned';

    public const STATUS_UPDATED = 'tasks.status-updated';

    public const DUE_REMINDER = 'tasks.due-reminder';

    public const COMMENT_ADDED = 'tasks.comment-added';

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::ASSIGNED,
                'label' => 'Task Assigned',
                'description' => 'Sent when a task is assigned to a user.',
                'supported_channels' => ['mail', 'database', 'sms', 'push'],
            ],
            [
                'key' => self::STATUS_UPDATED,
                'label' => 'Task Status Updated',
                'description' => 'Sent when a task changes status.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
            [
                'key' => self::DUE_REMINDER,
                'label' => 'Task Due Reminder',
                'description' => 'Sent when a task due date is approaching or overdue.',
                'supported_channels' => ['mail', 'database', 'sms', 'push'],
            ],
            [
                'key' => self::COMMENT_ADDED,
                'label' => 'Task Comment Added',
                'description' => 'Sent when a comment is added to a watched task.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
        ];
    }
}
