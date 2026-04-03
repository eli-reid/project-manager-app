<?php

namespace App\Domains\Timecards\Notifications;

class TimecardNotificationDefinitions
{
    public const APPROVED = 'timecards.approved';

    public const SUBMITTED = 'timecards.submitted';

    public const REJECTED = 'timecards.rejected';

    public const REMINDER = 'timecards.reminder';

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::APPROVED,
                'label' => 'Timecard Approved',
                'description' => 'Sent when a submitted timecard is approved.',
                'supported_channels' => ['mail', 'database', 'sms'],
            ],
            [
                'key' => self::SUBMITTED,
                'label' => 'Timecard Submitted',
                'description' => 'Sent when a timecard is submitted for review.',
                'supported_channels' => ['mail', 'database', 'sms'],
            ],
            [
                'key' => self::REJECTED,
                'label' => 'Timecard Rejected',
                'description' => 'Sent when a submitted timecard is rejected.',
                'supported_channels' => ['mail', 'database', 'sms'],
            ],
            [
                'key' => self::REMINDER,
                'label' => 'Timecard Reminder',
                'description' => 'Sent when a pending timecard needs attention.',
                'supported_channels' => ['mail', 'database', 'sms'],
            ],
        ];
    }
}
