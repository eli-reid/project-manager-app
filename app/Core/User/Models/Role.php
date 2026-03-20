<?php

namespace App\Core\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperRole
 */
class Role extends Model
{
    use HasFactory, HasUlids;

    public const BUILT_IN_ADMIN = 'Admin';

    public const BUILT_IN_USER = 'User';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'built_in',
        'access_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'built_in' => 'boolean',
        'access_level' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Role $role): bool {
            return ! $role->isBuiltIn();
        });
    }

    /**
     * The permissions that belong to the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        [$resource, $action] = array_pad(explode('.', $permission, 2), 2, null);

        if ($resource === null || $action === null) {
            return false;
        }

        return $this->permissions()
            ->where('resource', $resource)
            ->where('action', $action)
            ->exists();
    }

    /**
     * Determine if the role is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Determine if the role is a built-in system role.
     */
    public function isBuiltIn(): bool
    {
        return $this->built_in;
    }

    /**
     * Determine if the role is the built-in Admin role.
     */
    public function isAdmin(): bool
    {
        return strcasecmp($this->name, self::BUILT_IN_ADMIN) === 0 && $this->built_in;
    }

    /**
     * Toggle the active status of the role.
     */
    public function toggleStatus(): bool
    {
        if ($this->isBuiltIn()) {
            return false;
        }

        $this->is_active = ! $this->is_active;

        return $this->save();
    }
}
