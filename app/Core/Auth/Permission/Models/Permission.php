<?php

namespace App\Core\Auth\Permission\Models;

use App\Core\Auth\Role\Models\Role;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperPermission
 */
class Permission extends Model
{
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'resource',
        'action',
        'label',
        'description',
    ];

    /**
     * Get the full permission string as resource.action
     */
    public function getNameAttribute(): string
    {
        return "{$this->resource}.{$this->action}";
    }

    /**
     * The Role models that belong to the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
