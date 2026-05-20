<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\Identity\Models\User;
use App\Core\Scheduler\Livewire\Dashboard\Widget as SchedulerWidget;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Settings\Facades\Settings;
use App\Domains\Dailies\Livewire\Dashboard\Widget as DailyReportWidget;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Livewire\Dashboard\Widget as ProjectWidget;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\Dashboard\Widget as TimecardWidget;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * @param  array<int, string>  $permissions
 */
function dashboardWidgetUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Dashboard Widget Test Role '.str()->uuid(),
        'description' => 'Role created by dashboard widget tests.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

// ─── Registry Integration ─────────────────────────────────────────────────────

it('the timecards widget is registered in the container registry', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $keys = collect($registry->all())->pluck('key')->all();

    expect($keys)->toContain('timecards.my-week');
});

it('the timecards widget is in the personal section with half span', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $widget = collect($registry->all())->firstWhere('key', 'timecards.my-week');

    expect($widget)->not->toBeNull()
        ->and($widget['section'])->toBe('personal')
        ->and($widget['span'])->toBe('half');
});

it('the projects widget is registered in the container registry', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $keys = collect($registry->all())->pluck('key')->all();

    expect($keys)->toContain('projects.active-summary');
});

it('the projects widget is in the operations section with half span', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $widget = collect($registry->all())->firstWhere('key', 'projects.active-summary');

    expect($widget)->not->toBeNull()
        ->and($widget['section'])->toBe('operations')
        ->and($widget['span'])->toBe('half');
});

it('the scheduler widget is registered in the container registry', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $keys = collect($registry->all())->pluck('key')->all();

    expect($keys)->toContain('scheduler.task-health');
});

it('the scheduler widget is in the admin section with full span', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $widget = collect($registry->all())->firstWhere('key', 'scheduler.task-health');

    expect($widget)->not->toBeNull()
        ->and($widget['section'])->toBe('admin')
        ->and($widget['span'])->toBe('full');
});

it('the dailies widget is registered in the container registry', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $keys = collect($registry->all())->pluck('key')->all();

    expect($keys)->toContain('dailies.field-summary');
});

it('the dailies widget is in the operations section with half span', function (): void {
    $registry = app(DashboardWidgetRegistry::class);

    $widget = collect($registry->all())->firstWhere('key', 'dailies.field-summary');

    expect($widget)->not->toBeNull()
        ->and($widget['section'])->toBe('operations')
        ->and($widget['span'])->toBe('half');
});

it('renders dashboard widgets with consistent span classes', function (): void {
    $user = dashboardWidgetUserWithPermissions([
        'projects.view',
        'dailies.view',
        'timecards.view',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('lg:grid-cols-6', false)
        ->assertSee('lg:col-span-3', false)
        ->assertSee('lg:col-span-6', false);
});

it('routes the dailies widget to the mobile dailies index on the mobile dashboard', function (): void {
    $user = dashboardWidgetUserWithPermissions(['dailies.view']);

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk()
        ->assertSee(route('dailies.mobile.index'), false);
});

it('routes the timecards widget create action to the mobile timecard create route on the mobile dashboard', function (): void {
    Settings::set('app.week_start_day', 'sunday');
    $user = dashboardWidgetUserWithPermissions(['timecards.view', 'timecards.create']);

    $weekStart = app(TimecardWeekService::class)
        ->currentWeekStart()
        ->toDateString();

    $this->actingAs($user)
        ->get(route('mobile.dashboard'))
        ->assertOk()
        ->assertSee(route('timecards.mobile.create', ['week_starting' => $weekStart]), false);
});

it('expands single-widget sections to full width', function (): void {
    // A user with only timecards.view sees one widget (personal section) → full-width, no half-span multi-widget columns
    $user = dashboardWidgetUserWithPermissions(['timecards.view']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('lg:grid-cols-6', false)
        ->assertSee('lg:col-span-6', false)
        ->assertDontSee('lg:col-span-3', false);
});

// ─── Timecards Widget ─────────────────────────────────────────────────────────

it('renders the timecards widget for a user with timecards.view permission', function (): void {
    Settings::set('app.week_start_day', 'sunday');
    $user = dashboardWidgetUserWithPermissions(['timecards.view']);

    Livewire::actingAs($user)
        ->test(TimecardWidget::class)
        ->assertStatus(200)
        ->assertSee('My Timecards');
});

it('timecards widget shows a submitted timecard for the current week', function (): void {
    Settings::set('app.week_start_day', 'sunday');
    $user = dashboardWidgetUserWithPermissions(['timecards.view']);

    $weekStart = now()->startOfWeek(Carbon::SUNDAY);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'week_starting' => $weekStart->toDateString(),
        'week_ending' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => Timecard::STATUS_SUBMITTED,
        'total_hours' => 40.0,
    ]);

    Livewire::actingAs($user)
        ->test(TimecardWidget::class)
        ->assertStatus(200)
        ->assertSee('Submitted')
        ->assertSee('40.0');
});

it('timecards widget shows a no-timecard notice when current week has no entry', function (): void {
    Settings::set('app.week_start_day', 'sunday');
    $user = dashboardWidgetUserWithPermissions(['timecards.view']);

    Livewire::actingAs($user)
        ->test(TimecardWidget::class)
        ->assertStatus(200)
        ->assertSee('No timecard for the week');
});

it('timecards widget only shows the authenticated users own timecards', function (): void {
    Settings::set('app.week_start_day', 'sunday');
    $user = dashboardWidgetUserWithPermissions(['timecards.view']);
    $otherUser = User::factory()->create();

    $weekStart = now()->startOfWeek(Carbon::SUNDAY)->subWeeks(1);

    Timecard::factory()->create([
        'user_id' => $otherUser->id,
        'week_starting' => $weekStart->toDateString(),
        'week_ending' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => Timecard::STATUS_APPROVED,
        'total_hours' => 40.0,
    ]);

    Livewire::actingAs($user)
        ->test(TimecardWidget::class)
        ->assertStatus(200)
        ->assertDontSee('Approved');
});

// ─── Projects Widget ──────────────────────────────────────────────────────────

it('renders the projects widget for a user with projects.view permission', function (): void {
    $user = dashboardWidgetUserWithPermissions(['projects.view']);

    Livewire::actingAs($user)
        ->test(ProjectWidget::class)
        ->assertStatus(200)
        ->assertSee('Active Projects');
});

it('renders the projects widget for an admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(ProjectWidget::class)
        ->assertStatus(200)
        ->assertSee('Active Projects');
});

it('projects widget shows active project names for an admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Project::factory()->create([
        'name' => 'Alpha Project',
        'is_active' => true,
        'status' => ProjectStatusEnum::IN_PROGRESS,
    ]);

    Project::factory()->create([
        'name' => 'Beta Project',
        'is_active' => false,
        'status' => ProjectStatusEnum::IN_PROGRESS,
    ]);

    Livewire::actingAs($admin)
        ->test(ProjectWidget::class)
        ->assertStatus(200)
        ->assertSee('Alpha Project')
        ->assertDontSee('Beta Project');
});

it('projects widget hides leave projects from the active projects list', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Project::factory()->create([
        'name' => 'Operations Project',
        'is_active' => true,
        'status' => ProjectStatusEnum::IN_PROGRESS,
        'leave_category' => null,
    ]);

    Project::factory()->create([
        'name' => 'Sick Time',
        'is_active' => true,
        'status' => ProjectStatusEnum::ACTIVE,
        'leave_category' => 'sick',
    ]);

    Project::factory()->create([
        'name' => 'Vacation Time',
        'is_active' => true,
        'status' => ProjectStatusEnum::ACTIVE,
        'leave_category' => 'vacation',
    ]);

    Livewire::actingAs($admin)
        ->test(ProjectWidget::class)
        ->assertStatus(200)
        ->assertSee('Operations Project')
        ->assertDontSee('Sick Time')
        ->assertDontSee('Vacation Time');
});

// ─── Dailies Widget ───────────────────────────────────────────────────────────

it('renders the dailies widget for a user with dailies.view permission', function (): void {
    $user = dashboardWidgetUserWithPermissions(['dailies.view']);

    Livewire::actingAs($user)
        ->test(DailyReportWidget::class)
        ->assertStatus(200)
        ->assertSee('Daily Reports');
});

it('dailies widget scopes reports to the authenticated user', function (): void {
    $user = dashboardWidgetUserWithPermissions(['dailies.view']);
    $otherUser = User::factory()->create();

    $usersProject = Project::factory()->create(['name' => 'Users Project']);
    $otherProject = Project::factory()->create(['name' => 'Other Project']);

    DailyReport::factory()->create([
        'user_id' => $user->id,
        'project_id' => $usersProject->id,
        'status' => DailyReport::STATUS_SUBMITTED,
    ]);

    DailyReport::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherProject->id,
        'status' => DailyReport::STATUS_SUBMITTED,
    ]);

    Livewire::actingAs($user)
        ->test(DailyReportWidget::class)
        ->assertStatus(200)
        ->assertSee('Users Project')
        ->assertDontSee('Other Project');
});

it('dailies widget uses a single grouped status aggregation query', function (): void {
    $user = dashboardWidgetUserWithPermissions(['dailies.view']);

    $project = Project::factory()->create();

    DailyReport::factory()->count(2)->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    DailyReport::factory()->count(2)->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => DailyReport::STATUS_SUBMITTED,
    ]);

    DailyReport::factory()->count(1)->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => DailyReport::STATUS_APPROVED,
    ]);

    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    Livewire::actingAs($user)
        ->test(DailyReportWidget::class)
        ->assertStatus(200)
        ->assertSee('Daily Reports');

    $queries = collect($connection->getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => strtolower($query));

    $groupedAggregationQueries = $queries->filter(
        fn (string $query): bool => str_contains($query, 'from "daily_reports"')
            && str_contains($query, 'count(*) as aggregate')
            && str_contains($query, 'group by "status"')
    );

    $statusSpecificCountQueries = $queries->filter(
        fn (string $query): bool => str_contains($query, 'from "daily_reports"')
            && str_contains($query, 'count(*) as aggregate')
            && str_contains($query, 'where "status" =')
    );

    expect($groupedAggregationQueries)->toHaveCount(1)
        ->and($statusSpecificCountQueries)->toHaveCount(0);
});

// ─── Scheduler Widget ─────────────────────────────────────────────────────────

it('renders the scheduler widget for an admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(SchedulerWidget::class)
        ->assertStatus(200)
        ->assertSee('Scheduler Health');
});

it('renders the scheduler widget for a user with scheduler.view permission', function (): void {
    $user = dashboardWidgetUserWithPermissions(['scheduler.view']);

    Livewire::actingAs($user)
        ->test(SchedulerWidget::class)
        ->assertStatus(200)
        ->assertSee('Scheduler Health');
});

it('scheduler widget shows active tasks', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    ScheduledTask::query()->create([
        'name' => 'Daily Report Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
    ]);

    ScheduledTask::query()->create([
        'name' => 'Inactive Cleanup Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '10:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => false,
        'is_enabled' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(SchedulerWidget::class)
        ->assertStatus(200)
        ->assertSee('Daily Report Task')
        ->assertDontSee('Inactive Cleanup Task');
});
