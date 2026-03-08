<?php

namespace App\Core\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUlids, Notifiable, TwoFactorAuthenticatable;

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
        [$resource, $action] = array_pad(explode('.', $permission, 2), 2, null);

        if ($resource === null || $action === null) {
            return false;
        }

        return $this->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($resource, $action): void {
                $query->where('resource', $resource)
                    ->where('action', $action);
            })
            ->exists();
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

        return $this->roles()
            ->where('built_in', true)
            ->whereRaw('LOWER(name) = ?', [strtolower(Role::BUILT_IN_ADMIN)])
            ->exists();
    }
}
