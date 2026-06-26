<?php

namespace App\Core\Identity\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Announcement\Models\Announcement;
use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Database\Factories\UserFactory;
use App\Core\Identity\Services\UserAuthorizationSnapshotService;
use App\Core\Identity\Services\UserRelationshipRegistry;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Services\NotificationPreferenceService;
// Payroll relations moved to domain registry to decouple User model.
use App\Domains\Addresses\Models\Address;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Throwable;

/**
 * @property string $id
 * @property-read \App\Domains\Payroll\Models\PayrollEmployeeProfile|null $payrollProfile
 * @property-read Collection|\App\Domains\Payroll\Models\PayrollStatement[] $payrollStatements
 * @property-read Collection|\App\Domains\Payroll\Models\PayRun[] $createdPayRuns
 * @property-read Collection|\App\Domains\Payroll\Models\PayRun[] $approvedPayRuns
 *
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    public ?string $mailboxProvisioningPassword = null;

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, HasUlids, Notifiable, TwoFactorAuthenticatable;

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
        'phone',
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

    public function getNameAttribute(): string
    {
        $fullName = trim((string) $this->first_name.' '.(string) $this->last_name);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($this->username ?: $this->email);
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')->withTimestamps();
    }

    public function dismissedAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user_dismissals', 'user_id', 'announcement_id')
            ->withPivot('dismissed_at')
            ->withTimestamps();
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'user_addresses')
            ->withTimestamps();
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    public function projectTabPreferences(): HasMany
    {
        return $this->hasMany(ProjectTabUserPreference::class);
    }

    public function timecardRequiredEntry(): HasOne
    {
        return $this->hasOne(TimecardRequiredUser::class, 'user_id');
    }

    /**
     * Magic relationship resolution: delegate unknown relation methods to the
     * UserRelationshipRegistry so domain packages can register relations at runtime.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters)
    {
        // Resolve registry if available and the method is a registered relation
        if (app()->bound(UserRelationshipRegistry::class)) {
            $registry = app(UserRelationshipRegistry::class);

            if ($registry->has($method)) {
                $resolver = $registry->get($method);

                // Resolver must return an Eloquent Relation when invoked with ($user, ...$params)
                return $resolver($this, ...$parameters);
            }
        }

        return parent::__call($method, $parameters);
    }

    public function notificationPreferenceFor(string $notificationKey, string $channel): ?bool
    {
        return app(NotificationPreferenceService::class)->notificationPreferenceFor(
            $this,
            $notificationKey,
            $channel,
        );
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
        return UserFactory::new();
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

        app(UserAuthorizationSnapshotService::class)->flush($this);
    }

    public static function bumpPermissionCacheVersion(): void
    {
        try {
            app(UserAuthorizationSnapshotService::class)->bumpPermissionCacheVersion();
        } catch (Throwable) {
            // Ignore cache-store availability errors and fall back to request-scoped caching.
        }
    }

    /**
     * @return array{version:string, permission_keys:array<int, string>, has_admin_role:bool}
     */
    private function authorizationSnapshot(): array
    {
        $this->authorizationSnapshot = app(UserAuthorizationSnapshotService::class)->resolve(
            $this,
            $this->authorizationSnapshot,
        );

        return $this->authorizationSnapshot;
    }
}
