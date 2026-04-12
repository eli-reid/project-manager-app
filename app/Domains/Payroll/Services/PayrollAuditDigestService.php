<?php

namespace App\Domains\Payroll\Services;

use App\Core\Audit\Models\AuditLog;
use App\Domains\Payroll\Models\PayrollAuditDigest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PayrollAuditDigestService
{
    public function appendForAuditLog(AuditLog $auditLog, string $chainKey = 'payroll'): PayrollAuditDigest
    {
        $existing = PayrollAuditDigest::query()
            ->where('chain_key', $chainKey)
            ->where('audit_log_id', $auditLog->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $payloadHash = $this->payloadHash($auditLog);

        $previousDigest = PayrollAuditDigest::query()
            ->where('chain_key', $chainKey)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('digest');

        if (! is_string($previousDigest) || $previousDigest === '') {
            $previousDigest = null;
        }

        $digest = hash('sha256', ($previousDigest ?? 'genesis').'|'.$payloadHash.'|'.$auditLog->id);

        return PayrollAuditDigest::query()->create([
            'chain_key' => $chainKey,
            'audit_log_id' => $auditLog->id,
            'payload_hash' => $payloadHash,
            'digest' => $digest,
            'previous_digest' => $previousDigest,
            'is_valid' => true,
            'validated_at' => now(),
        ]);
    }

    public function backfillForPayrollActions(string $chainKey = 'payroll'): int
    {
        $auditLogIds = PayrollAuditDigest::query()
            ->where('chain_key', $chainKey)
            ->pluck('audit_log_id')
            ->filter()
            ->values();

        $missing = AuditLog::query()
            ->where('action', 'like', 'payroll.%')
            ->when($auditLogIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereNotIn('id', $auditLogIds))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($missing as $auditLog) {
            $this->appendForAuditLog($auditLog, $chainKey);
        }

        return $missing->count();
    }

    /**
     * @return array{total:int,valid:int,invalid:int}
     */
    public function validateChain(string $chainKey = 'payroll'): array
    {
        $digests = PayrollAuditDigest::query()
            ->with('auditLog')
            ->where('chain_key', $chainKey)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $previousDigest = null;
        $valid = 0;
        $invalid = 0;

        foreach ($digests as $digest) {
            $auditLog = $digest->auditLog;

            if ($auditLog === null) {
                $digest->forceFill([
                    'is_valid' => false,
                    'validated_at' => now(),
                    'metadata' => array_merge($digest->metadata ?? [], ['validation_error' => 'missing_audit_log']),
                ])->save();

                $invalid++;

                continue;
            }

            $expectedPayloadHash = $this->payloadHash($auditLog);
            $expectedDigest = hash('sha256', ($previousDigest ?? 'genesis').'|'.$expectedPayloadHash.'|'.$auditLog->id);

            $isValid = $digest->payload_hash === $expectedPayloadHash
                && $digest->previous_digest === $previousDigest
                && $digest->digest === $expectedDigest;

            $digest->forceFill([
                'is_valid' => $isValid,
                'validated_at' => now(),
                'metadata' => array_merge($digest->metadata ?? [], [
                    'validation_error' => $isValid ? null : 'digest_mismatch',
                ]),
            ])->save();

            if ($isValid) {
                $valid++;
            } else {
                $invalid++;
            }

            $previousDigest = $digest->digest;
        }

        return [
            'total' => $digests->count(),
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * @return array<int, PayrollAuditDigest>
     */
    public function latestDigestsForAuditLogs(Collection $auditLogs): array
    {
        $digests = PayrollAuditDigest::query()
            ->whereIn('audit_log_id', $auditLogs->pluck('id')->values())
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('audit_log_id');

        return $auditLogs
            ->map(fn (AuditLog $auditLog): ?PayrollAuditDigest => $digests->get($auditLog->id))
            ->filter()
            ->all();
    }

    private function payloadHash(AuditLog $auditLog): string
    {
        return hash('sha256', json_encode([
            'id' => (string) $auditLog->id,
            'action' => (string) $auditLog->action,
            'actor_type' => (string) ($auditLog->actor_type ?? ''),
            'actor_id' => (string) ($auditLog->actor_id ?? ''),
            'target_type' => (string) ($auditLog->target_type ?? ''),
            'target_id' => (string) ($auditLog->target_id ?? ''),
            'before' => $auditLog->before ?? [],
            'after' => $auditLog->after ?? [],
            'metadata' => $auditLog->metadata ?? [],
            'created_at' => optional($auditLog->created_at)->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
