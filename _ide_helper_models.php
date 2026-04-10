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
 * @property-read \App\Core\Identity\Models\User $creator
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

namespace App\Core\Audit\Models{
/**
 * @property string $id
 * @property string $action
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property string|null $target_type
 * @property string|null $target_id
 * @property array<array-key, mixed>|null $before
 * @property array<array-key, mixed>|null $after
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\CarbonImmutable $created_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $actor
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserAgent($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAuditLog {}
}

namespace App\Core\Auth\Permission\Models{
/**
 * @property string $id
 * @property string $resource
 * @property string $action
 * @property string $label
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Auth\Role\Models\Role> $roles
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

namespace App\Core\Auth\Role\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $built_in
 * @property int $access_level
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Auth\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Identity\Models\User> $users
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

namespace App\Core\Cpanel\Models{
/**
 * @property string $id
 * @property string $email
 * @property string|null $domain
 * @property bool $suspended
 * @property int $quota
 * @property int $usage
 * @property numeric $usage_percentage
 * @property array<array-key, mixed>|null $raw_data
 * @property string|null $user_id
 * @property \Carbon\CarbonImmutable|null $last_synced_at
 * @property bool $sync_failed
 * @property string|null $sync_error
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Core\Identity\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount active()
 * @method static \App\Core\Cpanel\Database\Factories\CachedEmailAccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount forDomain(string $domain)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount highUsage(float $threshold = 80)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount suspended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount syncFailed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereLastSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereRawData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereSyncError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereSyncFailed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereUsagePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CachedEmailAccount whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCachedEmailAccount {}
}

namespace App\Core\Identity\Models{
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Notification\Models\UserNotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Auth\Role\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \App\Core\Auth\User\Database\Factories\UserFactory factory($count = null, $state = [])
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

namespace App\Core\Notification\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property string $notification_key
 * @property string $channel
 * @property bool $enabled
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Core\Identity\Models\User $user
 * @method static \App\Core\Notification\Database\Factories\UserNotificationPreferenceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereNotificationKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserNotificationPreference {}
}

namespace App\Core\Queue\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string $connection
 * @property string $queue
 * @property string $payload
 * @property string $exception
 * @property string $failed_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereConnection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereException($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereUuid($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFailedJob {}
}

namespace App\Core\Queue\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $total_jobs
 * @property int $pending_jobs
 * @property int $failed_jobs
 * @property string $failed_job_ids
 * @property string|null $options
 * @property int|null $cancelled_at
 * @property int $created_at
 * @property int|null $finished_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereFailedJobIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereFailedJobs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch wherePendingJobs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobBatch whereTotalJobs($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperJobBatch {}
}

namespace App\Core\Queue\Models{
/**
 * @property int $id
 * @property string $queue
 * @property string $payload
 * @property int $attempts
 * @property int|null $reserved_at
 * @property int $available_at
 * @property int $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereAvailableAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJob whereReservedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperQueueJob {}
}

namespace App\Core\Queue\Models{
/**
 * @property string $id
 * @property string|null $job_uuid
 * @property string $job_class
 * @property string $queue
 * @property string $connection
 * @property int $attempt
 * @property string $status
 * @property \Carbon\CarbonImmutable $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $exception
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereAttempt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereConnection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereDurationMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereException($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereJobClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereJobUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QueueJobHistory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperQueueJobHistory {}
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
 * @property-read \App\Core\Identity\Models\User|null $creator
 * @property-read \App\Core\Identity\Models\User|null $updater
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

namespace App\Domains\Dailies\Models{
/**
 * @property string $id
 * @property string|null $project_id
 * @property string|null $custom_project_name
 * @property string $user_id
 * @property string|null $submitted_by_id
 * @property \Carbon\CarbonImmutable $report_date
 * @property string $status
 * @property array<array-key, mixed>|null $work_performed
 * @property array<array-key, mixed>|null $materials_used
 * @property array<array-key, mixed>|null $equipment_used
 * @property array<array-key, mixed>|null $safety_issues
 * @property array<array-key, mixed>|null $delays
 * @property array<array-key, mixed>|null $visitors
 * @property string|null $weather_condition
 * @property float|null $temperature
 * @property string $temperature_unit
 * @property float $total_regular_hours
 * @property float $total_overtime_hours
 * @property float $total_hours
 * @property string|null $additional_notes
 * @property string|null $rejection_reason
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property array<array-key, mixed>|null $onsite_employees
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Core\Identity\Models\User|null $submittedBy
 * @property-read \App\Core\Identity\Models\User $user
 * @method static \App\Domains\Dailies\Database\Factories\DailyReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereAdditionalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereCustomProjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereDelays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereEquipmentUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereMaterialsUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereOnsiteEmployees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereReportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereSafetyIssues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereSubmittedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereTemperatureUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereTotalOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereTotalRegularHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereVisitors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereWeatherCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport whereWorkPerformed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyReport withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDailyReport {}
}

namespace App\Domains\Documents\Models{
/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property string $original_name
 * @property string $stored_name
 * @property string|null $extension
 * @property string $mime_type
 * @property int $file_size
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $owner_scope
 * @property string $visibility
 * @property string $replace_mode
 * @property string|null $uploaded_by_id
 * @property \Carbon\CarbonImmutable|null $last_replaced_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Projects\Models\Project> $ownerProjects
 * @property-read int|null $owner_projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Core\Identity\Models\User> $ownerUsers
 * @property-read int|null $owner_users_count
 * @property-read \App\Core\Identity\Models\User|null $uploadedBy
 * @method static \App\Domains\Documents\Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document ownedByProject(string $projectId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document ownedByUser(string $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document projectOwned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document userOwned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereLastReplacedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereOwnerScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereReplaceMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereStorageDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereStoragePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereStoredName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUploadedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDocument {}
}

namespace App\Domains\Invoices\Models{
/**
 * @property string $id
 * @property string $project_id
 * @property string $vendor_name
 * @property string|null $invoice_number
 * @property \Carbon\CarbonImmutable $invoice_date
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property \Carbon\CarbonImmutable|null $payment_date
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $total_amount
 * @property \App\Domains\Invoices\Enums\InvoiceStatusEnum $status
 * @property string|null $notes
 * @property string $created_by
 * @property string|null $verified_by
 * @property \Carbon\CarbonImmutable|null $verified_at
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Core\Identity\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Invoices\Models\InvoiceLineItem> $lineItems
 * @property-read int|null $line_items_count
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Core\Identity\Models\User|null $verifier
 * @method static \App\Domains\Invoices\Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVendorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInvoice {}
}

namespace App\Domains\Invoices\Models{
/**
 * @property string $id
 * @property string $invoice_id
 * @property string $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $total
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Domains\Invoices\Models\Invoice|null $invoice
 * @method static \App\Domains\Invoices\Database\Factories\InvoiceLineItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLineItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInvoiceLineItem {}
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Dailies\Models\DailyReport> $dailyReports
 * @property-read int|null $daily_reports_count
 * @property-read \App\Core\Identity\Models\User|null $projectManager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Projects\Models\ProjectRoleAccess> $roleAccesses
 * @property-read int|null $role_accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Projects\Models\ProjectUserAccess> $userAccesses
 * @property-read int|null $user_accesses_count
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

namespace App\Domains\Projects\Models{
/**
 * @property-read \App\Core\Identity\Models\User|null $grantedBy
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Core\Auth\Role\Models\Role|null $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRoleAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRoleAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRoleAccess query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectRoleAccess {}
}

namespace App\Domains\Projects\Models{
/**
 * @property int $id
 * @property string $project_id
 * @property string $user_id
 * @property string|null $granted_by
 * @property array<array-key, mixed>|null $permission_keys
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Core\Identity\Models\User|null $grantedBy
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Core\Identity\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereGrantedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess wherePermissionKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUserAccess whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectUserAccess {}
}

namespace App\Domains\Stock\Models{
/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $project_id
 * @property string|null $po_number
 * @property string $status
 * @property string $urgency
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Stock\Models\StockOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Core\Identity\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder byUrgency(string $urgency)
 * @method static \App\Domains\Stock\Database\Factories\StockOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder ownedBy(string $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder wherePoNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereUrgency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockOrder {}
}

namespace App\Domains\Stock\Models{
/**
 * @property string $id
 * @property string $stock_order_id
 * @property int $quantity
 * @property string $item_name
 * @property string $status
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Domains\Stock\Models\StockOrder|null $stockOrder
 * @method static \App\Domains\Stock\Database\Factories\StockOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereStockOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockOrderItem {}
}

namespace App\Domains\Stock\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $urgency
 * @property string|null $notes
 * @property array<array-key, mixed> $template_items
 * @property bool $is_active
 * @property bool $is_global
 * @property string|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Core\Identity\Models\User|null $createdBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate availableToUser(string $userId)
 * @method static \App\Domains\Stock\Database\Factories\StockOrderTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereIsGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereTemplateItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate whereUrgency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrderTemplate withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockOrderTemplate {}
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
 * @property-read \App\Core\Identity\Models\User|null $assignedTo
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
 * @property-read \App\Core\Identity\Models\User|null $creator
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

namespace App\Domains\Timecards\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property \Carbon\CarbonImmutable $week_starting
 * @property \Carbon\CarbonImmutable $week_ending
 * @property string $status
 * @property float $total_hours
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $submitted_at
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property string|null $approved_by
 * @property \Carbon\CarbonImmutable|null $rejected_at
 * @property string|null $rejected_by
 * @property string|null $rejection_reason
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Core\Identity\Models\User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Timecards\Models\TimecardEntry> $entries
 * @property-read int|null $entries_count
 * @property-read \App\Core\Identity\Models\User|null $rejector
 * @property-read \App\Core\Identity\Models\User $user
 * @method static \App\Domains\Timecards\Database\Factories\TimecardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereWeekEnding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timecard whereWeekStarting($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTimecard {}
}

namespace App\Domains\Timecards\Models{
/**
 * @property string $id
 * @property string $timecard_id
 * @property string $user_id
 * @property string|null $project_id
 * @property string|null $custom_project_name
 * @property \Carbon\CarbonImmutable $date
 * @property string|null $start_time
 * @property float $hours
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Domains\Projects\Models\Project|null $project
 * @property-read \App\Domains\Timecards\Models\Timecard $timecard
 * @property-read \App\Core\Identity\Models\User $user
 * @method static \App\Domains\Timecards\Database\Factories\TimecardEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereCustomProjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereTimecardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimecardEntry whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTimecardEntry {}
}

