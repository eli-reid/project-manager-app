<?php
declare(strict_types=1);

namespace App\Core\Notification\Contracts;

use App\Core\Notification\DTO\NotificationMessage;

/**
 * Interface for registry notifications.
 *
 * Defines the contract for obtaining a notification key and converting an object into a notification message.
 */

interface RegistryNotification
{
    /**
     * Returns the unique notification key for this notification.
     *
     * @return string
     */
    public function notificationKey(): string;

    /**
     * Converts the given object into a notification message.
     *
     * @param object $notifiable
     * @return NotificationMessage
     */
    public function toNotificationMessage(object $notifiable): NotificationMessage;
}
