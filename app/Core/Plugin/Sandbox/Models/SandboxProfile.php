<?php

namespace App\Core\PluginSandbox\Models;

use App\Core\PluginSandbox\Database\Factories\SandboxProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \IdeHelperSandboxProfile
 */
class SandboxProfile extends Model
{
    /** @use HasFactory<SandboxProfileFactory> */
    use HasFactory;

    use HasUlids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const DRIVER_IN_PROCESS_GUARDED = 'in_process_guarded';

    public const DRIVER_QUEUE_WORKER = 'queue_worker';

    public const DRIVER_HTTP_BROKER = 'http_broker';

    protected $table = 'plugin_sandbox_profiles';

    protected $fillable = [
        'slug',
        'name',
        'isolation_driver',
        'status',
        'applies_to_trust_levels',
        'allowed_host_apis',
        'resource_limits',
        'metadata',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_trust_levels' => 'array',
            'allowed_host_apis' => 'array',
            'resource_limits' => 'array',
            'metadata' => 'array',
            'last_verified_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SandboxProfileFactory
    {
        return SandboxProfileFactory::new();
    }
}
