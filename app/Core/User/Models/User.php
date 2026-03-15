<?php

namespace App\Core\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Throwable;

class User extends Authenticatable
{
    private const SESSION_PERMISSION_SNAPSHOT_PREFIX = 'auth.permission_snapshot.';

    private const PERMISSION_CACHE_VERSION_KEY = 'auth.permission_cache.version';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUlids, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var array{version:string, permission_keys:array<int, string>, has_admin_role:bool}|null
     */
    private ?array $authorizationSnapshot = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'company_email',
        'password',
        'is_admin',
        'is_built_in',
        'is_active',
        'password_change_required',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_built_in' => 'boolean',
            'is_active' => 'boolean',
            'password_change_required' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        if (! str_contains($permission, '.')) {
            return false;
        }

        return in_array($permission, $this->authorizationSnapshot()['permission_keys'], true);
    }

    /**
     * Resolve the model factory for this model.
     */
    protected static function newFactory()
    {
        return \App\Core\User\Database\Factories\UserFactory::new();
    }

    /**
     * Determine if the user has administrator access.
     */
    public function isAdmin(): bool
    {
        if ((bool) $this->is_admin) {
            return true;
        }

        return $this->authorizationSnapshot()['has_admin_role'];
    }

    public function flushAuthorizationCache(): void
    {
        $this->authorizationSnapshot = null;

        if ($this->hasSessionContext()) {
            session()->forget($this->sessionSnapshotKey());
        }
    }

    public static function bumpPermissionCacheVersion(): void
    {
        try {
            Cache::forever(self::PERMISSION_CACHE_VERSION_KEY, now()->format('Uu'));
        } catch (Throwable) {
            // Ignore cache-store availability errors and fall back to request-scoped caching.
        }
    }

    /**
     * @return array{version:string, permission_keys:array<int, string>, has_admin_role:bool}
     */
    private function authorizationSnapshot(): array
    {
        if ($this->authorizationSnapshot !== null) {
            return $this->authorizationSnapshot;
        }

        $cacheVersion = $this->permissionCacheVersion();

        if ($this->hasSessionContext()) {
            $snapshot = session()->get($this->sessionSnapshotKey());

            if (is_array($snapshot)
                && isset($snapshot['version'], $snapshot['permission_keys'], $snapshot['has_admin_role'])
                && $snapshot['version'] === $cacheVersion
                && is_array($snapshot['permission_keys'])
                && is_bool($snapshot['has_admin_role'])) {
                /** @var array{version:string, permission_keys:array<int, string>, has_admin_role:bool} $snapshot */
                $this->authorizationSnapshot = $snapshot;

                return $this->authorizationSnapshot;
            }
        }

        $this->authorizationSnapshot = $this->buildAuthorizationSnapshot($cacheVersion);

        if ($this->hasSessionContext()) {
            session()->put($this->sessionSnapshotKey(), $this->authorizationSnapshot);
        }

        return $this->authorizationSnapshot;
    }

    /**
     * @return array{version:string, permission_keys:array<int, string>, has_admin_role:bool}
     */
    private function buildAuthorizationSnapshot(string $cacheVersion): array
    {
        $roles = $this->roles()
            ->where('roles.is_active', true)
            ->with(['permissions:id,resource,action'])
            ->get(['roles.id', 'roles.name', 'roles.built_in']);

        $permissionKeys = $roles
            ->flatMap(function (Role $role): array {
                return $role->permissions
                    ->map(fn (Permission $permission): string => $permission->resource.'.'.$permission->action)
                    ->all();
            })
            ->unique()
            ->values()
            ->all();

        $hasAdminRole = $roles->contains(
            fn (Role $role): bool => (bool) $role->built_in && strcasecmp($role->name, Role::BUILT_IN_ADMIN) === 0
        );

        return [
            'version' => $cacheVersion,
            'permission_keys' => $permissionKeys,
            'has_admin_role' => $hasAdminRole,
        ];
    }

    private function sessionSnapshotKey(): string
    {
        return self::SESSION_PERMISSION_SNAPSHOT_PREFIX.(string) $this->getAuthIdentifier();
    }

    private function hasSessionContext(): bool
    {
        if (! app()->bound('session')) {
            return false;
        }

        try {
            session()->getName();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function permissionCacheVersion(): string
    {
        try {
            return (string) Cache::get(self::PERMISSION_CACHE_VERSION_KEY, '1');
        } catch (Throwable) {
            return '1';
        }
    }
}
