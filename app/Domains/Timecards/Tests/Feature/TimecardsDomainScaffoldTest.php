<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use App\Domains\Timecards\Livewire\Admin\Timecards\Form as AdminForm;
use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Livewire\Admin\Timecards\Show as AdminShow;
use App\Domains\Timecards\Livewire\User\Timecards\Form as UserForm;
use App\Domains\Timecards\Livewire\User\Timecards\Index as UserIndex;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from timecards admin routes', function (): void {
    get(route('admin.timecards.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without timecard view permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('admin.timecards.index'))
        ->assertForbidden();
});

it('allows users with timecards.view-all to access admin timecards index', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view-all']);
    $owner = User::factory()->create();

    Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_DRAFT,
        'total_hours' => 32.5,
    ]);

    actingAs($reviewer);

    Livewire::test(Index::class)
        ->assertSee('Timecards');
});

it('forbids users with only timecards.view from admin timecards index', function (): void {
    $viewer = userWithTimecardDomainPermissions(['timecards.view']);

    actingAs($viewer);

    get(route('admin.timecards.index'))
        ->assertForbidden();
});

it('filters admin timecards index by status and employee', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all']);
    $matchingUser = User::factory()->create(['first_name' => 'Taylor', 'last_name' => 'Admin']);
    $otherUser = User::factory()->create(['first_name' => 'Other', 'last_name' => 'Employee']);

    $matching = Timecard::factory()->create([
        'user_id' => $matchingUser->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'total_hours' => 16,
    ]);

    Timecard::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Timecard::STATUS_DRAFT,
        'total_hours' => 8,
    ]);

    actingAs($reviewer);

    Livewire::test(Index::class)
        ->set('statusFilter', Timecard::STATUS_SUBMITTED)
        ->set('userFilter', (string) $matchingUser->id)
        ->assertSee((string) $matching->user?->first_name)
        ->assertSee('16.00')
        ->assertDontSee('8.00');
});

it('renders the admin timecards sidebar link for reviewers', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view-all']);

    actingAs($reviewer);

    get(route('admin.timecards.index'))
        ->assertOk()
        ->assertSee(route('admin.timecards.index'), false)
        ->assertSee('Timecards');
});

it('bulk approves submitted timecards and skips ineligible rows', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.approve']);
    $employee = User::factory()->create();

    $submittedOne = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => '2026-06-07',
        'week_ending' => '2026-06-13',
    ]);

    $submittedTwo = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => '2026-06-14',
        'week_ending' => '2026-06-20',
    ]);

    $draft = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => '2026-06-21',
        'week_ending' => '2026-06-27',
    ]);

    actingAs($reviewer);

    Livewire::test(Index::class)
        ->set('bulkAction', 'approve')
        ->set('selectedTimecardIds', [(string) $submittedOne->id, (string) $submittedTwo->id, (string) $draft->id])
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect($submittedOne->fresh()->status)->toBe(Timecard::STATUS_APPROVED)
        ->and($submittedTwo->fresh()->status)->toBe(Timecard::STATUS_APPROVED)
        ->and($draft->fresh()->status)->toBe(Timecard::STATUS_DRAFT);
});

it('bulk rejects submitted timecards with a rejection reason', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.reject']);
    $employee = User::factory()->create();

    $submitted = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_SUBMITTED,
    ]);

    actingAs($reviewer);

    Livewire::test(Index::class)
        ->set('bulkAction', 'reject')
        ->set('bulkRejectionReason', 'Needs corrections')
        ->set('selectedTimecardIds', [(string) $submitted->id])
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect($submitted->fresh()->status)->toBe(Timecard::STATUS_REJECTED)
        ->and($submitted->fresh()->rejection_reason)->toBe('Needs corrections');
});

it('bulk deletes non-approved timecards and keeps approved rows', function (): void {
    $reviewer = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.delete']);
    $employee = User::factory()->create();

    $draft = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => '2026-07-05',
        'week_ending' => '2026-07-11',
    ]);

    $approved = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_APPROVED,
        'week_starting' => '2026-07-12',
        'week_ending' => '2026-07-18',
    ]);

    actingAs($reviewer);

    Livewire::test(Index::class)
        ->set('bulkAction', 'delete')
        ->set('selectedTimecardIds', [(string) $draft->id, (string) $approved->id])
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('timecards', ['id' => $draft->id]);
    $this->assertDatabaseHas('timecards', ['id' => $approved->id]);
});

it('redirects guests from user timecards routes', function (): void {
    get(route('timecards.index'))
        ->assertRedirect(route('login'));
});

it('allows authorized users to access non-admin timecards routes', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('timecards.index'))
        ->assertOk()
        ->assertSee('My Timecards');

    get(route('timecards.create'))
        ->assertOk()
        ->assertSee('Create Timecard');

    get(route('timecards.show', $timecard))
        ->assertOk()
        ->assertSee('Timecard Details');

    get(route('timecards.edit', $timecard))
        ->assertOk()
        ->assertSee('Edit Timecard');
});

it('allows authorized users to access non-admin mobile timecards routes', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('timecards.mobile.index'))
        ->assertOk()
        ->assertSee('My Timecards');

    get(route('timecards.mobile.create'))
        ->assertOk()
        ->assertSee('Create Timecard');

    get(route('timecards.mobile.show', $timecard))
        ->assertOk()
        ->assertSee('Timecard Details');

    get(route('timecards.mobile.edit', $timecard))
        ->assertOk()
        ->assertSee('Edit Timecard');
});

it('allows users with timecard permissions to access their own index', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'total_hours' => 12.5,
    ]);

    actingAs($user);

    Livewire::test(UserIndex::class)
        ->assertSee('My Timecards');
});

it('creates draft timecards through the lifecycle service and prevents duplicates', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);
    $service = app(TimecardLifecycleService::class);

    $timecard = $service->createDraftForUser($user, '2026-03-22', [
        'notes' => 'Initial draft',
    ]);

    expect($timecard->status)->toBe(Timecard::STATUS_DRAFT)
        ->and($timecard->week_starting?->toDateString())->toBe('2026-03-22');

    $response = actingAs($user)->getJson(route('api.timecards.check-existing', [
        'week_starting' => '2026-03-23',
    ]));

    $response->assertSuccessful()
        ->assertJson([
            'exists' => true,
        ]);

    $service->updateDraft($timecard, ['notes' => 'Updated draft']);

    expect($timecard->fresh()->notes)->toBe('Updated draft');
});

it('submits and resets rejected timecards through the lifecycle service', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);
    $manager = userWithTimecardDomainPermissions(['timecards.view-all', 'timecards.approve', 'timecards.reject', 'timecards.edit']);
    $service = app(TimecardLifecycleService::class);

    $timecard = $service->createDraftForUser($user, '2026-03-29');
    $timecard->entries()->create([
        'user_id' => $user->id,
        'project_id' => null,
        'custom_project_name' => 'General Work',
        'date' => '2026-03-30',
        'start_time' => '07:00:00',
        'hours' => 8,
        'notes' => 'Worked onsite',
    ]);

    $submitted = $service->submit($timecard);
    expect($submitted->status)->toBe(Timecard::STATUS_SUBMITTED);

    $rejected = $service->reject($submitted, $manager, 'Need corrections');
    expect($rejected->status)->toBe(Timecard::STATUS_REJECTED)
        ->and($rejected->rejection_reason)->toBe('Need corrections');

    $reset = $service->resetToDraft($rejected);
    expect($reset->status)->toBe(Timecard::STATUS_DRAFT)
        ->and($reset->rejection_reason)->toBeNull();
});

it('creates a timecard with entries through the user form component', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);

    actingAs($user);

    Livewire::test(UserForm::class)
        ->set('week_starting', '2026-04-12')
        ->set('notes', 'Weekly draft')
        ->set('entries.0.day_of_week', 1) // Monday
        ->set('entries.0.hours', '8')
        ->set('entries.0.custom_project_name', 'Field Work')
        ->set('entries.0.notes', 'Crew setup')
        ->call('save')
        ->assertRedirect();

    $timecard = Timecard::query()->where('user_id', $user->id)->whereDate('week_starting', '2026-04-12')->first();

    expect($timecard)->not->toBeNull()
        ->and($timecard?->entries()->count())->toBe(1)
        ->and($timecard?->fresh()->total_hours)->toBe(8.0);
});

it('creates a timecard for another user through the admin form component', function (): void {
    $admin = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.create', 'timecards.edit']);
    $employee = User::factory()->create();

    actingAs($admin);

    Livewire::test(AdminForm::class)
        ->set('user_id', (string) $employee->id)
        ->set('week_starting', '2026-04-19')
        ->set('notes', 'Created by admin')
        ->set('entries.0.day_of_week', 1) // Monday
        ->set('entries.0.hours', '7.5')
        ->set('entries.0.custom_project_name', 'Warehouse Work')
        ->call('save')
        ->assertRedirect();

    $timecard = Timecard::query()
        ->where('user_id', $employee->id)
        ->whereDate('week_starting', '2026-04-19')
        ->first();

    expect($timecard)->not->toBeNull()
        ->and($timecard?->entries()->count())->toBe(1)
        ->and($timecard?->fresh()->total_hours)->toBe(7.5);
});

it('updates a timecard through the admin form component', function (): void {
    $admin = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.edit']);
    $employee = User::factory()->create();
    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => '2026-04-26',
        'week_ending' => '2026-05-02',
        'notes' => 'Original notes',
    ]);

    $entry = $timecard->entries()->create([
        'user_id' => $employee->id,
        'project_id' => null,
        'custom_project_name' => 'Initial Work',
        'date' => '2026-04-27',
        'start_time' => '07:00:00',
        'hours' => 4,
        'notes' => 'Initial entry',
    ]);

    actingAs($admin);

    Livewire::test(AdminForm::class, ['timecard' => $timecard])
        ->set('week_starting', '2026-05-03')
        ->set('notes', 'Adjusted by admin')
        ->set('entries.0.id', (string) $entry->id)
        ->set('entries.0.day_of_week', 1)
        ->set('entries.0.hours', '9')
        ->set('entries.0.notes', 'Adjusted entry')
        ->call('save')
        ->assertRedirect();

    expect($timecard->fresh()->week_starting?->toDateString())->toBe('2026-05-03')
        ->and($timecard->fresh()->notes)->toBe('Adjusted by admin')
        ->and($timecard->fresh()->total_hours)->toBe(9.0)
        ->and($timecard->fresh()->entries()->first()?->notes)->toBe('Adjusted entry');
});

it('updates an existing entry day without duplicating rows or inflating total hours', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit']);
    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => '2026-05-10',
        'week_ending' => '2026-05-16',
        'total_hours' => 8,
    ]);

    $entry = $timecard->entries()->create([
        'user_id' => $user->id,
        'project_id' => null,
        'custom_project_name' => 'Field Work',
        'date' => '2026-05-11',
        'start_time' => '07:00:00',
        'hours' => 8,
        'notes' => 'Initial day',
    ]);

    actingAs($user);

    Livewire::test(UserForm::class, ['timecard' => $timecard])
        ->set('entries.0.id', (string) $entry->id)
        ->set('entries.0.day_of_week', 3)
        ->set('entries.0.hours', '8')
        ->set('entries.0.notes', 'Moved day')
        ->call('save')
        ->assertRedirect();

    $freshTimecard = $timecard->fresh();
    $freshEntry = $freshTimecard?->entries()->first();

    expect($freshTimecard)->not->toBeNull()
        ->and($freshTimecard?->entries()->count())->toBe(1)
        ->and($freshTimecard?->total_hours)->toBe(8.0)
        ->and((string) $freshEntry?->id)->toBe((string) $entry->id)
        ->and($freshEntry?->date?->toDateString())->toBe('2026-05-13')
        ->and($freshEntry?->notes)->toBe('Moved day');
});

it('prevents updates to approved timecards through the lifecycle service', function (): void {
    $admin = userWithTimecardDomainPermissions(['timecards.view-all', 'timecards.edit']);
    $employee = User::factory()->create();
    $service = app(TimecardLifecycleService::class);

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_APPROVED,
        'week_starting' => '2026-05-10',
        'week_ending' => '2026-05-16',
        'notes' => 'Approved snapshot',
    ]);

    $timecard->entries()->create([
        'user_id' => $employee->id,
        'project_id' => null,
        'custom_project_name' => 'Approved Work',
        'date' => '2026-05-11',
        'start_time' => '08:00:00',
        'hours' => 8,
        'notes' => 'Do not change',
    ]);

    expect(fn (): Timecard => $service->updateForAdmin($timecard, $employee, ['notes' => 'Attempted admin edit']))
        ->toThrow(ValidationException::class, 'Approved timecards may not be edited.');

    expect($timecard->fresh()->notes)->toBe('Approved snapshot');
});

it('allows admins to review and transition submitted timecards', function (): void {
    $owner = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);
    $reviewer = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.approve', 'timecards.reject', 'timecards.edit']);
    $service = app(TimecardLifecycleService::class);

    $timecard = $service->createDraftForUser($owner, '2026-04-05');
    $timecard->entries()->create([
        'user_id' => $owner->id,
        'project_id' => null,
        'custom_project_name' => 'Review Work',
        'date' => '2026-04-06',
        'start_time' => '08:00:00',
        'hours' => 6,
        'notes' => 'Ready for approval',
    ]);
    $submitted = $service->submit($timecard);

    actingAs($reviewer);

    Livewire::test(AdminShow::class, ['timecard' => $submitted])
        ->assertSee('Timecard Review')
        ->call('approve');

    expect($submitted->fresh()->status)->toBe(Timecard::STATUS_APPROVED);
});

it('allows admins to delete non-approved timecards from review', function (): void {
    $admin = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.delete']);
    $employee = User::factory()->create();
    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($admin);

    Livewire::test(AdminShow::class, ['timecard' => $timecard])
        ->call('delete')
        ->assertRedirect(route('admin.timecards.index'));

    $this->assertDatabaseMissing('timecards', ['id' => $timecard->id]);
});

it('allows a user to create a new timecard for the same week after the previous was deleted', function (): void {
    $user = userWithTimecardDomainPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit', 'timecards.delete']);
    $service = app(TimecardLifecycleService::class);

    $original = $service->createDraftForUser($user, '2026-08-03');

    $service->delete($original);

    $this->assertDatabaseMissing('timecards', ['id' => $original->id]);

    $replacement = $service->createDraftForUser($user, '2026-08-03');

    expect($replacement->week_starting?->toDateString())->toBe($original->week_starting?->toDateString())
        ->and($replacement->status)->toBe(Timecard::STATUS_DRAFT);
});

it('denies delete ability for approved timecards even for admin reviewers', function (): void {
    $admin = userWithTimecardDomainPermissions(['timecards.view', 'timecards.view-all', 'timecards.delete']);
    $employee = User::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    expect(Gate::forUser($admin)->allows('delete', $timecard))->toBeFalse();

    $this->assertDatabaseHas('timecards', ['id' => $timecard->id]);
});

it('recalculates timecard total hours through the entry observer', function (): void {
    $timecard = Timecard::factory()->create(['total_hours' => 0]);

    $entry = TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $timecard->user_id,
        'project_id' => null,
        'custom_project_name' => 'Observer Work',
        'hours' => 5.5,
    ]);

    expect($timecard->fresh()->total_hours)->toBe(5.5);

    $entry->update(['hours' => 7.25]);
    expect($timecard->fresh()->total_hours)->toBe(7.25);

    $entry->delete();
    expect($timecard->fresh()->total_hours)->toBe(0.0);
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithTimecardDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Timecards Test Role '.str()->uuid(),
        'description' => 'Role for timecards domain tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
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
