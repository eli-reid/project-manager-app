<?php

namespace App\Domains\Payroll\Observers;

use App\Domains\Payroll\Services\PayrollAuditTrailService;
use Illuminate\Database\Eloquent\Model;

class PayrollAuditObserver
{
    public function __construct(private readonly PayrollAuditTrailService $auditTrail) {}

    public function created(Model $model): void
    {
        $entity = $this->entityKey($model);

        $this->auditTrail->record("payroll.{$entity}.created", $model, [
            'after' => $this->auditTrail->modelSnapshot($model),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(['updated_at'])
            ->all();

        if ($changes === []) {
            return;
        }

        $before = [];
        $after = [];

        foreach ($changes as $field => $value) {
            $before[$field] = $model->getOriginal($field);
            $after[$field] = $model->getAttribute($field);
        }

        $entity = $this->entityKey($model);

        $this->auditTrail->record("payroll.{$entity}.updated", $model, [
            'before' => $before,
            'after' => $after,
        ]);
    }

    public function deleted(Model $model): void
    {
        $entity = $this->entityKey($model);

        $this->auditTrail->record("payroll.{$entity}.deleted", $model, [
            'before' => $this->auditTrail->modelSnapshot($model),
        ]);
    }

    public function restored(Model $model): void
    {
        $entity = $this->entityKey($model);

        $this->auditTrail->record("payroll.{$entity}.restored", $model, [
            'after' => $this->auditTrail->modelSnapshot($model),
        ]);
    }

    public function forceDeleted(Model $model): void
    {
        $entity = $this->entityKey($model);

        $this->auditTrail->record("payroll.{$entity}.force-deleted", null, [
            'target_type' => $model->getMorphClass(),
            'target_id' => (string) $model->getKey(),
            'before' => $this->auditTrail->modelSnapshot($model),
        ]);
    }

    private function entityKey(Model $model): string
    {
        return str(class_basename($model))->kebab()->plural()->toString();
    }
}
