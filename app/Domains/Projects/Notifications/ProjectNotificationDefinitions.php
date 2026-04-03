<?php

namespace App\Domains\Projects\Notifications;

class ProjectNotificationDefinitions
{
    public const ACCESS_GRANTED = 'projects.access-granted';

    public const ACCESS_REVOKED = 'projects.access-revoked';

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::ACCESS_GRANTED,
                'label' => 'Project Access Granted',
                'description' => 'Sent when a user is granted access to a project.',
                'supported_channels' => ['mail', 'database', 'sms', 'push'],
            ],
            [
                'key' => self::ACCESS_REVOKED,
                'label' => 'Project Access Revoked',
                'description' => 'Sent when a user loses access to a project.',
                'supported_channels' => ['mail', 'database', 'sms', 'push'],
            ],
        ];
    }
}
