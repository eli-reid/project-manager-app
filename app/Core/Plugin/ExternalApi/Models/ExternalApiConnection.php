<?php

namespace App\Core\PluginExternalApi\Models;

use App\Core\PluginExternalApi\Database\Factories\ExternalApiConnectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \IdeHelperExternalApiConnection
 */
class ExternalApiConnection extends Model
{
    /** @use HasFactory<ExternalApiConnectionFactory> */
    use HasFactory;

    use HasUlids;

    public const STATUS_STAGED = 'staged';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const TRUST_EXTERNAL_API_ONLY = 'external_api_only';

    public const EXECUTION_OUT_OF_PROCESS = 'out_of_process';

    protected $table = 'external_api_connections';

    protected $fillable = [
        'slug',
        'name',
        'driver',
        'base_url',
        'auth_type',
        'status',
        'trust_level',
        'execution_mode',
        'allowed_scopes',
        'metadata',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'allowed_scopes' => 'array',
            'metadata' => 'array',
            'last_verified_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExternalApiConnectionFactory
    {
        return ExternalApiConnectionFactory::new();
    }
}
