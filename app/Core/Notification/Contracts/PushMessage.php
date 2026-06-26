<?php

namespace App\Core\Notification\Contracts;

interface PushMessage
{
    public function to(): string;

    public function body(): string;

    public function from(): ?string;
}