<?php

namespace App\Core\Notification\Contracts;

use App\Core\Identity\Models\User;

interface EmailNotification
{
    public function toEmail(User $user): EmailMessage;
}
