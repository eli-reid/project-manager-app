<?php

namespace App\Core\PluginSystem\Models;

use App\Core\Identity\Models\User;
use App\Core\PluginSystem\Database\Factories\InstalledPluginFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperInstalledPlugin
 */
class InstalledPlugin extends Model
{
    /** @use HasFactory<InstalledPluginFactory> */
    use HasFactory;

    use HasUlids;

    public const STATUS_STAGED = 'staged';

    public const STATUS_INSTALLED = 'installed';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_QUARANTINED = 'quarantined';

    public const SECURITY_PENDING_REVIEW = 'pending_review';

    public const SECURITY_APPROVED = 'approved';

    public const SECURITY_BLOCKED = 'blocked';

    public const SOURCE_MARKETPLACE = 'marketplace';

    public const SOURCE_LOCAL = 'local';

    public const SOURCE_BUNDLED = 'bundled';

    protected $table = 'installed_plugins';

    protected $fillable = [
        'slug',
        'name',
        'provider_class',
        'package_name',
        'version',
        'source_type',
        'status',
        'security_status',
        'manifest_checksum',
        'signature_fingerprint',
        'capabilities',
        'required_permissions',
        'metadata',
        'installed_at',
        'last_reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'required_permissions' => 'array',
            'metadata' => 'array',
            'installed_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected static function newFactory(): InstalledPluginFactory
    {
        return InstalledPluginFactory::new();
    }
}
