<?php

namespace App\Core\Audit\Services;

use App\Core\Audit\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function record(string $action, mixed $target = null, array $context = [], ?Authenticatable $actor = null): AuditLog
    {
        [$targetType, $targetId] = $this->resolveTarget($target, $context);
        [$ipAddress, $userAgent] = $this->resolveRequestMetadata();

        $resolvedActor = $actor ?? Auth::user();

        return AuditLog::query()->create([
            'action' => $action,
            'actor_type' => $resolvedActor instanceof Model ? $resolvedActor->getMorphClass() : null,
            'actor_id' => $resolvedActor instanceof Model ? (string) $resolvedActor->getKey() : null,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before' => Arr::get($context, 'before'),
            'after' => Arr::get($context, 'after'),
            'metadata' => Arr::except($context, ['before', 'after', 'target_type', 'target_id']),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveTarget(mixed $target, array $context): array
    {
        if ($target instanceof Model) {
            return [$target->getMorphClass(), (string) $target->getKey()];
        }

        $targetType = Arr::get($context, 'target_type');
        $targetId = Arr::get($context, 'target_id');

        if (! is_string($targetType) || $targetType === '') {
            $targetType = null;
        }

        if (! is_string($targetId) || $targetId === '') {
            $targetId = null;
        }

        return [$targetType, $targetId];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveRequestMetadata(): array
    {
        if (! app()->bound('request')) {
            return [null, null];
        }

        $request = request();
        $userAgent = $request->userAgent();

        if (! is_string($userAgent) || $userAgent === '') {
            $userAgent = null;
        }

        return [$request->ip(), $userAgent];
    }
}
