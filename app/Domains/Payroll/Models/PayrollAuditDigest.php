<?php

namespace App\Domains\Payroll\Models;

use App\Core\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayrollAuditDigest
 */
class PayrollAuditDigest extends Model
{
    use HasUlids;

    protected $fillable = [
        'chain_key',
        'audit_log_id',
        'payload_hash',
        'digest',
        'previous_digest',
        'is_valid',
        'validated_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'validated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }
}
