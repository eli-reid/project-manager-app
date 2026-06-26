<?php

namespace App\Domains\Payroll\Tasks;

use App\Core\Scheduler\Contracts\SchedulableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Domains\Payroll\Services\PayrollAuditDigestService;
use App\Domains\Payroll\Services\PayrollAuditTrailService;
use Illuminate\Support\Facades\Log;

class PayrollDigestValidationTask implements SchedulableTask
{
    public function __construct(private readonly ScheduledTask $task) {}

    public function dispatchJob(): void
    {
        $chainKey = (string) (($this->task->task_config['chain_key'] ?? 'payroll'));
        $digestService = app(PayrollAuditDigestService::class);

        $backfilled = $digestService->backfillForPayrollActions($chainKey);
        $result = $digestService->validateChain($chainKey);

        app(PayrollAuditTrailService::class)->record('payroll.audit.digest-validation.completed', null, [
            'target_type' => 'payroll-audit-digest-chain',
            'target_id' => $chainKey,
            'chain_key' => $chainKey,
            'backfilled' => $backfilled,
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
        ]);

        if ($result['invalid'] > 0) {
            Log::alert('Payroll audit digest validation detected invalid records.', [
                'task_id' => (string) $this->task->id,
                'feature_type' => $this->task->availableTask?->feature_type,
                'chain_key' => $chainKey,
                'invalid_count' => $result['invalid'],
            ]);

            app(PayrollAuditTrailService::class)->record('payroll.audit.digest-validation.alerted', null, [
                'target_type' => 'payroll-audit-digest-chain',
                'target_id' => $chainKey,
                'invalid' => $result['invalid'],
            ]);
        }
    }
}
