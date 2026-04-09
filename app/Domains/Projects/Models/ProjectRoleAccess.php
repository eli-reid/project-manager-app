<?php

namespace App\Domains\Projects\Models;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProjectRoleAccess
 */
class ProjectRoleAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'role_id',
        'granted_by',
        'permission_keys',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permission_keys' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
