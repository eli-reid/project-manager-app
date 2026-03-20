<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Core\Announcement\Models{
/**
 * @property string $id
 * @property string $title
 * @property string $content
 * @property \App\Core\Announcement\Enums\AnnouncementType $type
 * @property bool $is_active
 * @property bool $is_dismissable
 * @property \Carbon\CarbonImmutable|null $start_date
 * @property \Carbon\CarbonImmutable|null $end_date
 * @property string $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Core\User\Models\User $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement active()
 * @method static \App\Core\Announcement\Database\Factories\AnnouncementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereIsDismissable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAnnouncement {}
}

namespace App\Core\Scheduler\Models{
/**
 * @property string $id
 * @property string $feature_type
 * @property string $name
 * @property string|null $description
 * @property array<array-key, mixed>|null $task_config
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Scheduler\Models\ScheduledTask> $scheduledTasks
 * @property-read int|null $scheduled_tasks_count
 * @method static \App\Core\Scheduler\Database\Factories\AvailableTaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereFeatureType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereTaskConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailableTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAvailableTask {}
}

namespace App\Core\Scheduler\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $available_task_id
 * @property string $schedule_type
 * @property string $time
 * @property string $timezone
 * @property array<array-key, mixed>|null $days_of_week
 * @property int|null $day_of_month
 * @property int|null $month
 * @property \Carbon\CarbonImmutable|null $specific_date
 * @property string $repeat_frequency
 * @property int $repeat_interval
 * @property \Carbon\CarbonImmutable|null $repeat_until
 * @property int|null $max_occurrences
 * @property bool $is_active
 * @property bool $is_enabled
 * @property \Carbon\Carbon|null $last_run_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property int $run_count
 * @property array<array-key, mixed>|null $task_config
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Core\Scheduler\Models\AvailableTask $availableTask
 * @property-read \App\Core\User\Models\User|null $creator
 * @property-read \App\Core\User\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask due()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask enabled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask runnable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereAvailableTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereDayOfMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereDaysOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereLastRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereMaxOccurrences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereNextRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereRepeatFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereRepeatInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereRepeatUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereRunCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereScheduleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereSpecificDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereTaskConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScheduledTask whereUpdatedBy($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperScheduledTask {}
}

namespace App\Core\Settings\Models{
/**
 * @property int|null $id
 * @property string $key
 * @property string|null $value
 * @property string|null $default_value
 * @property string|null $display_name
 * @property string|null $description
 * @property string|null $type
 * @property string|null $group
 * @property array<array-key, mixed>|null $options
 * @property int|null $order
 * @property bool|null $is_public
 * @property bool|null $is_visible
 * @property bool|null $is_required
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property bool|null $encrypted
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereIsVisible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SettingsSqlite whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSettingsSqlite {}
}

namespace App\Core\User\Models{
/**
 * @property string $id
 * @property string $resource
 * @property string $action
 * @property string $label
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\User\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereResource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPermission {}
}

namespace App\Core\User\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $built_in
 * @property int $access_level
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\User\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\User\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereAccessLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereBuiltIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRole {}
}

namespace App\Core\User\Models{
/**
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property string $email
 * @property string|null $company_email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property bool $is_built_in
 * @property bool $is_active
 * @property bool $password_change_required
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\User\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \App\Core\User\Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsBuiltIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordChangeRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Domains\Addresses\Models{
/**
 * @property string $id
 * @property string $address1
 * @property string|null $address2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string $country
 * @property string|null $client_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Domains\Clients\Models\Client|null $client
 * @method static \App\Domains\Addresses\Database\Factories\AddressFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereAddress1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereZip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAddress {}
}

namespace App\Domains\Clients\Models{
/**
 * @property string $id
 * @property string $company_name
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $notes
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Addresses\Models\Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Projects\Models\Project> $projects
 * @property-read int|null $projects_count
 * @method static \App\Domains\Clients\Database\Factories\ClientFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperClient {}
}

namespace App\Domains\Projects\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $project_number
 * @property string|null $description
 * @property \App\Domains\Projects\Enums\ProjectStatusEnum $status
 * @property \Carbon\CarbonImmutable|null $start_date
 * @property \Carbon\CarbonImmutable|null $end_date
 * @property string|null $client_id
 * @property string|null $address_id
 * @property string|null $project_manager_id
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Domains\Addresses\Models\Address|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Addresses\Models\Address> $availableClientAddresses
 * @property-read int|null $available_client_addresses_count
 * @property-read \App\Domains\Clients\Models\Client|null $client
 * @property-read \App\Core\User\Models\User|null $projectManager
 * @method static \App\Domains\Projects\Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProject {}
}

namespace App\Domains\Tasks\Models{
/**
 * @property string $id
 * @property string $project_id
 * @property string|null $task_category_id
 * @property string|null $parent_task_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property numeric|null $estimated_hours
 * @property int $completion_percentage
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string|null $assigned_to
 * @property bool $is_billable
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Core\User\Models\User|null $assignedTo
 * @property-read \App\Domains\Tasks\Models\TaskCategory|null $category
 * @property-read Task|null $parentTask
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $subTasks
 * @property-read int|null $sub_tasks_count
 * @method static \App\Domains\Tasks\Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCompletionPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereEstimatedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereIsBillable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereParentTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTask {}
}

namespace App\Domains\Tasks\Models{
/**
 * @property string $id
 * @property string|null $project_id
 * @property string|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskCategory> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskCategory> $childrenRecursive
 * @property-read int|null $children_recursive_count
 * @property-read TaskCategory|null $parent
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Tasks\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \App\Domains\Tasks\Database\Factories\TaskCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTaskCategory {}
}

namespace App\Domains\Tasks\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $task_category_id
 * @property string $priority
 * @property numeric|null $estimated_hours
 * @property bool $is_billable
 * @property array<array-key, mixed>|null $template_tasks
 * @property bool $is_active
 * @property string|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Domains\Tasks\Models\TaskCategory|null $category
 * @property-read \App\Core\User\Models\User|null $creator
 * @method static \App\Domains\Tasks\Database\Factories\TaskTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereEstimatedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereIsBillable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereTaskCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereTemplateTasks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTemplate withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTaskTemplate {}
}

