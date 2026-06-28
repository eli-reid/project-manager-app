<?php

namespace App\Core\Notification\Contracts;

use App\Core\Notification\DTO\NotificationMessage;

interface RegistryNotification
{
    public function notificationKey(): string;

    public function toNotificationMessage(object $notifiable): NotificationMessage;
}
