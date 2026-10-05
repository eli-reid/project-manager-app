<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\Mobile\Timecards\EntryForm;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Index as MobileIndex;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Show as MobileShow;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Settings::set('app.week_start_day', 'monday');
    $this->travelTo('2026-10-07 09:00:00');
});

/**
 * @param  array<string, mixed>  $attributes
 */
function mobileWeekTimecard(User $user, string $weekStart = '2026-10-05', array $attributes = []): Timecard
{
    return Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => $weekStart,
        'week_ending' => Carbon::parse($weekStart)->addDays(6)->toDateString(),
        'total_hours' => 0,
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function mobileWeekEntry(Timecard $timecard, array $attributes = []): TimecardEntry
{
    return $timecard->entries()->create([
        'user_id' => $timecard->user_id,
        'project_id' => null,
        'custom_project_name' => 'Shop',
        'date' => $timecard->week_starting->toDateString(),
        'start_time' => '07:00',
        'hours' => 8,
        'notes' => null,
        ...$attributes,
    ]);
}

it('redirects guests from mobile timecard routes', function (): void {
    $timecard = mobileWeekTimecard(User::factory()->create());

    get(route('timecards.mobile.index'))->assertRedirect(route('login'));
    get(route('timecards.mobile.create'))->assertRedirect(route('login'));
    get(route('timecards.mobile.entries.create', $timecard))->assertRedirect(route('login'));
});

it('lists this week and last week first on the mobile index', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.create']);
    mobileWeekTimecard($user, '2026-10-05', ['total_hours' => 12.5]);
    $older = mobileWeekTimecard($user, '2026-09-14');

    actingAs($user);

    get(route('timecards.mobile.index'))
        ->assertOk()
        ->assertSeeLivewire(MobileIndex::class)
        ->assertSeeInOrder(['This Week', 'Oct 5', '12.50', 'Last Week', 'Sep 28', 'Start', 'Other Weeks', 'Sep 14'])
        ->assertSee(route('timecards.mobile.create', ['week_starting' => '2026-09-28']), false)
        ->assertSee(route('timecards.mobile.show', $older), false);
});

it('does not link unstarted weeks for users who cannot create timecards', function (): void {
    $user = mobileTimecardUser(['timecards.view']);

    actingAs($user);

    get(route('timecards.mobile.index'))
        ->assertOk()
        ->assertSee('Not started')
        ->assertDontSee(route('timecards.mobile.create', ['week_starting' => '2026-10-05']), false);
});

it('auto creates a draft timecard when opening a week that has none', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.create']);

    actingAs($user);

    $response = get(route('timecards.mobile.create', ['week_starting' => '2026-09-30']));

    $timecard = Timecard::query()->where('user_id', $user->id)->sole();

    expect($timecard->week_starting->toDateString())->toBe('2026-09-28')
        ->and($timecard->status)->toBe(Timecard::STATUS_DRAFT);

    $response->assertRedirect(route('timecards.mobile.show', $timecard));
});

it('opens the existing timecard instead of creating a duplicate', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.create']);
    $timecard = mobileWeekTimecard($user);

    actingAs($user);

    get(route('timecards.mobile.create', ['week_starting' => '2026-10-05']))
        ->assertRedirect(route('timecards.mobile.show', $timecard));

    expect(Timecard::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('defaults to the current week when the requested week is invalid', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.create']);

    actingAs($user);

    get(route('timecards.mobile.create', ['week_starting' => 'not-a-date']))->assertRedirect();

    expect(Timecard::query()->where('user_id', $user->id)->sole()->week_starting->toDateString())->toBe('2026-10-05');
});

it('forbids auto creating timecards without the create permission', function (): void {
    $user = mobileTimecardUser(['timecards.view']);

    actingAs($user);

    get(route('timecards.mobile.create'))->assertForbidden();

    expect(Timecard::query()->count())->toBe(0);
});

it('shows week entries grouped by day and ordered by start time', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create(['name' => 'Harbor Bridge']);

    mobileWeekEntry($timecard, ['date' => '2026-10-06', 'start_time' => '13:00', 'custom_project_name' => 'Afternoon Job', 'hours' => 3]);
    mobileWeekEntry($timecard, ['date' => '2026-10-06', 'start_time' => '06:30', 'project_id' => $project->id, 'custom_project_name' => null, 'hours' => 5]);
    mobileWeekEntry($timecard, ['date' => '2026-10-05', 'start_time' => '07:00', 'custom_project_name' => 'Monday Shop', 'hours' => 8]);

    actingAs($user);

    get(route('timecards.mobile.show', $timecard))
        ->assertOk()
        ->assertSeeLivewire(MobileShow::class)
        ->assertSee('Week of Oct 5')
        ->assertSeeInOrder(['Monday', 'Monday Shop', '8.00h', 'Tuesday', '8.00h', 'Harbor Bridge', '6:30 AM', '5.00h', 'Afternoon Job', '1:00 PM', '3.00h', 'Wednesday'])
        ->assertSee('Add Entry')
        ->assertSee(route('timecards.mobile.entries.create', ['timecard' => $timecard, 'date' => '2026-10-07']), false);
});

it('hides add entry actions on non-draft timecards', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user, attributes: ['status' => Timecard::STATUS_SUBMITTED]);
    $entry = mobileWeekEntry($timecard);

    actingAs($user);

    get(route('timecards.mobile.show', $timecard))
        ->assertOk()
        ->assertDontSee(route('timecards.mobile.entries.create', $timecard), false)
        ->assertDontSee(route('timecards.mobile.entries.edit', ['timecard' => $timecard, 'entry' => $entry]), false);
});

it('submits a draft timecard from the week view', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit', 'timecards.submit']);
    $timecard = mobileWeekTimecard($user);
    mobileWeekEntry($timecard);

    Livewire::actingAs($user)
        ->test(MobileShow::class, ['timecard' => $timecard])
        ->assertSeeHtml('wire:click="submit"')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDontSeeHtml('wire:click="submit"')
        ->assertDontSee('Add Entry');

    expect($timecard->fresh()->status)->toBe(Timecard::STATUS_SUBMITTED);
});

it('shows an error when submitting a week without entries', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit', 'timecards.submit']);
    $timecard = mobileWeekTimecard($user);

    Livewire::actingAs($user)
        ->test(MobileShow::class, ['timecard' => $timecard])
        ->call('submit')
        ->assertHasErrors(['entries'])
        ->assertSee('Add at least one time entry before submitting.');

    expect($timecard->fresh()->status)->toBe(Timecard::STATUS_DRAFT);
});

it('redirects the legacy mobile edit route to the week view', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);

    actingAs($user);

    get('/timecards/mobile/'.$timecard->id.'/edit')
        ->assertRedirect('/timecards/mobile/'.$timecard->id);
});

it('renders the add entry form with the requested day preselected', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);

    actingAs($user);

    get(route('timecards.mobile.entries.create', ['timecard' => $timecard, 'date' => '2026-10-09']))
        ->assertOk()
        ->assertSeeLivewire(EntryForm::class)
        ->assertSee('Add Entry')
        ->assertSee('Save &amp; Add Another', false);

    Livewire::withQueryParams(['date' => '2026-10-09'])
        ->actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->assertSet('date', '2026-10-09');
});

it('defaults new entries to today and ignores dates outside the week', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);

    Livewire::withQueryParams(['date' => '2026-12-01'])
        ->actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->assertSet('date', '2026-10-07');
});

it('prefills project and start time from the latest work entry', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create();
    mobileWeekEntry($timecard, ['project_id' => $project->id, 'custom_project_name' => null, 'start_time' => '06:30']);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->assertSet('project_id', (string) $project->id)
        ->assertSet('start_time', '06:30')
        ->assertSet('hours', '');
});

it('creates an entry and returns to the week view', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create();
    $costCode = CostCode::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->set('date', '2026-10-06')
        ->set('project_id', (string) $project->id)
        ->set('cost_code_id', (string) $costCode->id)
        ->set('start_time', '07:00')
        ->set('hours', '8.00')
        ->set('notes', 'Framing')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('timecards.mobile.show', $timecard));

    $entry = $timecard->entries()->sole();

    expect($entry->date->toDateString())->toBe('2026-10-06')
        ->and($entry->project_id)->toBe($project->id)
        ->and($entry->cost_code_id)->toBe($costCode->id)
        ->and($entry->hours)->toBe(8.0)
        ->and($entry->notes)->toBe('Framing')
        ->and($timecard->fresh()->total_hours)->toBe(8.0);
});

it('saves and resets the form for the next day', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->set('date', '2026-10-05')
        ->set('project_id', (string) $project->id)
        ->set('start_time', '07:00')
        ->set('hours', '8.00')
        ->call('saveAndAddAnother')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('date', '2026-10-06')
        ->assertSet('hours', '')
        ->assertSet('project_id', (string) $project->id)
        ->assertSet('start_time', '07:00')
        ->assertSee('Saved 8.00 h on Mon, Oct 5.');

    expect($timecard->entries()->count())->toBe(1);
});

it('validates entry fields', function (string $field, mixed $value, string $error): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->set('custom_project_name', 'Shop')
        ->set('hours', '8.00')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$error]);

    expect($timecard->entries()->count())->toBe(0);
})->with([
    'zero hours' => ['hours', '0', 'hours'],
    'too many hours' => ['hours', '25', 'hours'],
    'date outside week' => ['date', '2026-10-12', 'date'],
    'missing custom project name' => ['custom_project_name', '', 'custom_project_name'],
    'bad start time' => ['start_time', '7am', 'start_time'],
]);

it('rejects a cost code from another project', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create();
    $otherCostCode = CostCode::factory()->create();

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->set('project_id', (string) $project->id)
        ->set('cost_code_id', (string) $otherCostCode->id)
        ->set('hours', '8.00')
        ->call('save')
        ->assertHasErrors(['cost_code_id']);
});

it('clears the custom project name and cost code when the project changes', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->set('custom_project_name', 'Temporary job')
        ->set('cost_code_id', 'stale')
        ->set('project_id', (string) $project->id)
        ->assertSet('custom_project_name', null)
        ->assertSet('cost_code_id', null);
});

it('switches to a leave entry type and saves against the leave project', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $sickProject = Project::factory()->create(['leave_category' => 'sick', 'name' => 'Sick Leave']);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard])
        ->assertSee('Sick')
        ->call('setEntryType', 'sick')
        ->assertSet('entryType', 'sick')
        ->assertSet('project_id', (string) $sickProject->id)
        ->assertSee('hrs sick remaining')
        ->set('hours', '8.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($timecard->entries()->sole()->project_id)->toBe($sickProject->id);
});

it('updates an existing entry', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $entry = mobileWeekEntry($timecard, ['hours' => 8]);

    actingAs($user);

    get(route('timecards.mobile.entries.edit', ['timecard' => $timecard, 'entry' => $entry]))
        ->assertOk()
        ->assertSee('Edit Entry')
        ->assertSee('Delete Entry')
        ->assertDontSee('Save &amp; Add Another', false);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard, 'entry' => $entry])
        ->assertSet('hours', '8.00')
        ->assertSet('custom_project_name', 'Shop')
        ->set('hours', '6.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('timecards.mobile.show', $timecard));

    expect($entry->fresh()->hours)->toBe(6.5)
        ->and($timecard->fresh()->total_hours)->toBe(6.5);
});

it('deletes an entry', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $entry = mobileWeekEntry($timecard);

    Livewire::actingAs($user)
        ->test(EntryForm::class, ['timecard' => $timecard, 'entry' => $entry])
        ->call('delete')
        ->assertRedirect(route('timecards.mobile.show', $timecard));

    expect(TimecardEntry::query()->whereKey($entry->id)->exists())->toBeFalse()
        ->and($timecard->fresh()->total_hours)->toBe(0.0);
});

it('returns not found for an entry from another timecard', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $timecard = mobileWeekTimecard($user);
    $otherEntry = mobileWeekEntry(mobileWeekTimecard($user, '2026-09-28'));

    actingAs($user);

    get(route('timecards.mobile.entries.edit', ['timecard' => $timecard, 'entry' => $otherEntry]))
        ->assertNotFound();
});

it('forbids adding entries to another users or non-draft timecard', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);
    $otherUsersTimecard = mobileWeekTimecard(User::factory()->create());
    $submittedTimecard = mobileWeekTimecard($user, attributes: ['status' => Timecard::STATUS_SUBMITTED]);

    actingAs($user);

    get(route('timecards.mobile.entries.create', $otherUsersTimecard))->assertForbidden();
    get(route('timecards.mobile.entries.create', $submittedTimecard))->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function mobileTimecardUser(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Mobile Timecards Test Role '.str()->uuid(),
        'description' => 'Role for mobile timecard tests',
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
