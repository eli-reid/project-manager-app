<?php

namespace App\Domains\Payroll\Services;

use App\Core\Audit\Services\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class PayrollAuditTrailService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PayrollAuditDigestService $digestService,
    ) {}

    public function record(string $action, mixed $target = null, array $context = [], ?Authenticatable $actor = null): void
    {
        $auditLog = $this->auditLogger->record($action, $target, $context, $actor);

        if (str_starts_with($action, 'payroll.')) {
            $this->digestService->appendForAuditLog($auditLog);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function modelSnapshot(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['updated_at'])
            ->map(fn (mixed $value): mixed => $this->normalizeValue($value))
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            return collect($value)->map(fn (mixed $child): mixed => $this->normalizeValue($child))->all();
        }

        return $value;
    }
}
