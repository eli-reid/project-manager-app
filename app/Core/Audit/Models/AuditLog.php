<?php

namespace App\Core\Audit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperAuditLog
 */
class AuditLog extends Model
{
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'actor_type',
        'actor_id',
        'target_type',
        'target_id',
        'before',
        'after',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
