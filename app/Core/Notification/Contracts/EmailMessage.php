<?php

namespace App\Core\Notification\Contracts;  

interface EmailMessage
{
    public function subject(): string;

    public function body(): string;

    public function to(): string;

    public function from(): ?string;

    public function cc(): array;

    public function bcc(): array;

    public function attachments(): array;
}