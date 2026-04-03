<?php

namespace App\Core\Notification\Support;

use App\Core\Notification\Services\NotificationRegistry;

class NotificationEventCatalog
{
    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public static function definitions(): array
    {
        return app(NotificationRegistry::class)->definitions();
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return app(NotificationRegistry::class)->keys();
    }

    public static function isRegistered(string $notificationKey): bool
    {
        return app(NotificationRegistry::class)->has($notificationKey);
    }
}
