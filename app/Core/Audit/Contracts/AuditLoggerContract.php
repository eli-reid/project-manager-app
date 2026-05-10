<?php

namespace App\Core\Audit\Contracts;

use App\Core\Audit\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuditLoggerContract
{
    public function record(string $action, mixed $target = null, array $context = [], ?Authenticatable $actor = null): AuditLog;
}
