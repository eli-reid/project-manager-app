<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Form as MobileForm;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Index as MobileIndex;
use App\Domains\Timecards\Livewire\Mobile\Timecards\Show as MobileShow;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from mobile timecard create route', function (): void {
    get(route('timecards.mobile.create'))
        ->assertRedirect(route('login'));
});

it('redirects guests from mobile timecard edit route', function (): void {
    $owner = User::factory()->create();
    $timecard = Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    get(route('timecards.mobile.edit', $timecard))
        ->assertRedirect(route('login'));
});

it('renders the mobile timecard create form', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    actingAs($user);

    get(route('timecards.mobile.create'))
        ->assertOk()
        ->assertSeeLivewire(MobileForm::class)
        ->assertSee('Week Range')
        ->assertSee('Tap a quick hour chip for faster entry.');
});

it('renders the mobile timecard index', function (): void {
    $user = mobileTimecardUser(['timecards.view']);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('timecards.mobile.index'))
        ->assertOk()
        ->assertSeeLivewire(MobileIndex::class)
        ->assertSee('Start New Timecard');
});

it('renders the mobile timecard show page for the owner', function (): void {
    $user = mobileTimecardUser(['timecards.view']);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('timecards.mobile.show', $timecard))
        ->assertOk()
        ->assertSeeLivewire(MobileShow::class)
        ->assertSee('Entries');
});

it('renders the mobile timecard edit form for the owner', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('timecards.mobile.edit', $timecard))
        ->assertOk()
        ->assertSeeLivewire(MobileForm::class);
});

it('uses the mobile layout on the create form', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    actingAs($user);

    get(route('timecards.mobile.create'))
        ->assertOk()
        ->assertSee('Create Timecard');
});

it('shows only configured week-start dates in the mobile week picker', function (): void {
    Settings::set('app.week_start_day', 'monday');
    $user = mobileTimecardUser(['timecards.create']);

    $component = Livewire::actingAs($user)->test(MobileForm::class);

    preg_match_all('/<option value="(\d{4}-\d{2}-\d{2})">/', $component->html(), $matches);

    expect($matches[1])->not->toBeEmpty();
    expect(collect($matches[1])->every(fn (string $date): bool => Carbon::parse($date)->isMonday()))->toBeTrue();
});

it('renders the cancel link to the mobile timecard index on the create form', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    actingAs($user);

    get(route('timecards.mobile.create'))
        ->assertOk()
        ->assertSee(route('timecards.mobile.index'), false);
});

it('renders a submit action on the mobile header button', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    actingAs($user);

    get(route('timecards.mobile.create'))
        ->assertOk()
        ->assertSee('form="mobile-timecard-form"', false)
        ->assertSee('type="submit"', false);
});

it('creates a draft timecard via the mobile form and redirects to mobile show', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    $weekStart = Carbon::parse('last monday')->toDateString();

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('week_starting', $weekStart)
        ->set('entries.0.day_of_week', 1)
        ->set('entries.0.hours', '8.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('timecards', [
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);
});

it('updates an existing draft timecard via the mobile form', function (): void {
    $user = mobileTimecardUser(['timecards.view', 'timecards.edit']);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);

    Livewire::actingAs($user)
        ->test(MobileForm::class, ['timecard' => $timecard])
        ->set('entries.0.day_of_week', 2)
        ->set('entries.0.hours', '6.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('timecards', [
        'id' => $timecard->id,
        'status' => Timecard::STATUS_DRAFT,
    ]);
});

it('validates required week_starting on mobile form', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('week_starting', '')
        ->call('save')
        ->assertHasErrors(['week_starting']);
});

it('applies a quick hour preset to a mobile entry row', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('entries.0.hours', '1.00')
        ->call('applyHoursPreset', 0, '8.00')
        ->assertSet('entries.0.hours', '8.00');
});

it('applies a quick start time preset to a mobile entry row', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('entries.0.start_time', '05:00')
        ->call('applyStartTimePreset', 0, '07:30')
        ->assertSet('entries.0.start_time', '07:30');
});

it('saves multiple entries for the same configured week day', function (): void {
    Settings::set('app.week_start_day', 'monday');
    $user = mobileTimecardUser(['timecards.create']);

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('week_starting', '2026-03-30')
        ->set('entries.0.day_of_week', 1)
        ->set('entries.0.hours', '4.00')
        ->call('addEntry')
        ->set('entries.1.day_of_week', 1)
        ->set('entries.1.hours', '4.00')
        ->call('save')
        ->assertHasNoErrors();

    $timecard = Timecard::query()->where('user_id', $user->id)->latest()->firstOrFail();

    expect($timecard->entries()->whereDate('date', '2026-03-30')->count())->toBe(2);
});

it('normalizes the selected week before saving mobile entries', function (): void {
    Settings::set('app.week_start_day', 'monday');
    $user = mobileTimecardUser(['timecards.create']);

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('week_starting', '2026-03-29')
        ->set('entries.0.day_of_week', 0)
        ->set('entries.0.hours', '8.00')
        ->call('save')
        ->assertHasNoErrors();

    $timecard = Timecard::query()->where('user_id', $user->id)->latest()->firstOrFail();

    expect($timecard->week_starting?->toDateString())->toBe('2026-03-23');
    expect($timecard->entries()->whereDate('date', '2026-03-29')->count())->toBe(1);
});

it('clears a custom project name when selecting a configured project', function (): void {
    $user = mobileTimecardUser(['timecards.create']);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('entries.0.custom_project_name', 'Temporary job name')
        ->set('entries.0.project_id', (string) $project->id)
        ->assertSet('entries.0.custom_project_name', null);
});

it('does not persist a custom project name with a configured project', function (): void {
    $user = mobileTimecardUser(['timecards.create']);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(MobileForm::class)
        ->set('entries.0.project_id', (string) $project->id)
        ->set('entries.0.custom_project_name', 'Stale project name')
        ->set('entries.0.hours', '8.00')
        ->call('save')
        ->assertHasNoErrors();

    $timecard = Timecard::query()->where('user_id', $user->id)->latest()->firstOrFail();

    expect($timecard->entries()->firstOrFail()->custom_project_name)->toBeNull();
});

it('requires confirmation before removing an entry with a cost code', function (): void {
    $user = mobileTimecardUser(['timecards.create']);

    $component = Livewire::actingAs($user)->test(MobileForm::class)
        ->set('entries.0.cost_code_id', 'cost-code-id');

    expect($component->html())->toContain('wire:confirm="Remove this entry? Its entered details will be lost."');
});

it('prevents unauthorized user from creating timecards via mobile form', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('timecards.mobile.create'))
        ->assertForbidden();
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
        'description' => 'Role for mobile timecard form tests',
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
