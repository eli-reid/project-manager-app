<?php

use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Announcement\Livewire\Admin\Announcements\Form;
use App\Core\Announcement\Livewire\Admin\Announcements\Index;
use App\Core\Announcement\Livewire\Dashboard\Widget;
use App\Core\Announcement\Models\Announcement;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use Livewire\Livewire;

it('allows users with announcement view permission to access the admin index', function (): void {
    $user = announcementUserWithPermissions(['announcements.view']);

    $this->actingAs($user)
        ->get(route('admin.announcements.index'))
        ->assertSuccessful()
        ->assertSee('Announcements');
});

it('shows dashboard create link only for users with announcement create permission', function (): void {
    $creator = announcementUserWithPermissions(['announcements.view', 'announcements.create']);
    $viewer = announcementUserWithPermissions(['announcements.view']);

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Company Announcements')
        ->assertSee(route('admin.announcements.create', absolute: false));

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Company Announcements')
        ->assertDontSee(route('admin.announcements.create', absolute: false));
});

it('forbids users without announcement permissions from admin index', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.announcements.index'))
        ->assertForbidden();
});

it('shows the dashboard announcement widget for authenticated users without announcement permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'General Update',
        'content' => 'Visible on dashboard for all authenticated users.',
        'is_active' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Company Announcements')
        ->assertSee('General Update')
        ->assertDontSee(route('admin.announcements.create', absolute: false));
});

it('returns announcements from the api for users with announcement view permission', function (): void {
    $user = announcementUserWithPermissions(['announcements.view']);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'API Visible',
        'content' => 'Visible through api.',
        'type' => AnnouncementType::Warning,
        'is_active' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $dismissedAnnouncement = Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Dismissed API Announcement',
        'content' => 'Dismissed before loading the api.',
        'type' => AnnouncementType::Info,
        'is_active' => true,
        'is_dismissable' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $dismissedAnnouncement->dismissFor($user);

    $response = $this->actingAs($user)
        ->getJson(route('api.announcements.index'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [[
                'id',
                'title',
                'content',
                'type',
                'is_dismissable',
                'start_date',
                'end_date',
                'created_at',
            ]],
        ])
        ->assertJsonPath('data.0.title', 'API Visible')
        ->assertJsonPath('data.0.type', AnnouncementType::Warning->value)
        ->assertJsonCount(1, 'data');
});

it('forbids announcement api access without announcement view permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->getJson(route('api.announcements.index'))
        ->assertForbidden();
});

it('requires authentication for the announcement api', function (): void {
    $this->getJson(route('api.announcements.index'))
        ->assertUnauthorized();
});

it('stores announcements with validated data through livewire form', function (): void {
    $user = announcementUserWithPermissions(['announcements.create', 'announcements.view']);

    $this->actingAs($user);

    Livewire::test(Form::class)
        ->set('title', 'Maintenance Window')
        ->set('content', 'Systems will be unavailable from 9PM to 10PM.')
        ->set('type', AnnouncementType::Warning->value)
        ->set('is_active', true)
        ->set('is_dismissable', true)
        ->set('start_date', now()->subHour()->format('Y-m-d\\TH:i'))
        ->set('end_date', now()->addHour()->format('Y-m-d\\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $announcement = Announcement::query()->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement?->title)->toBe('Maintenance Window')
        ->and($announcement?->type)->toBe(AnnouncementType::Warning)
        ->and((string) $announcement?->created_by)->toBe((string) $user->id);
});

it('updates and deletes announcements when authorized through livewire components', function (): void {
    $user = announcementUserWithPermissions([
        'announcements.view',
        'announcements.edit',
        'announcements.delete',
    ]);

    $announcement = Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Old title',
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['announcement' => $announcement])
        ->set('title', 'New title')
        ->set('content', 'Updated content')
        ->set('type', AnnouncementType::Info->value)
        ->set('is_active', true)
        ->set('is_dismissable', false)
        ->set('start_date', now()->subDay()->format('Y-m-d\\TH:i'))
        ->set('end_date', now()->addDay()->format('Y-m-d\\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($announcement->fresh()->title)->toBe('New title');

    Livewire::test(Index::class)
        ->call('deleteAnnouncement', (string) $announcement->id)
        ->assertHasNoErrors();

    expect(Announcement::query()->find($announcement->id))->toBeNull();
});

it('returns only active announcements for active scope', function (): void {
    $user = User::factory()->create();

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Visible',
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Inactive',
        'is_active' => false,
    ]);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Not started',
        'is_active' => true,
        'start_date' => now()->addDay(),
    ]);

    $titles = Announcement::query()->active()->pluck('title')->all();

    expect($titles)->toBe(['Visible']);
});

it('excludes soft deleted announcements from the active scope', function (): void {
    $user = User::factory()->create();

    $announcement = Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Soft Deleted',
        'is_active' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $announcement->delete();

    expect(Announcement::query()->active()->pluck('title')->all())->toBe([]);
});

it('hides dismissed announcements from the dashboard widget for the dismissing user', function (): void {
    $user = announcementUserWithPermissions(['announcements.view']);

    $dismissableAnnouncement = Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Dismiss Me',
        'content' => 'Dismissable content.',
        'is_active' => true,
        'is_dismissable' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Keep Me',
        'content' => 'Still visible content.',
        'is_active' => true,
        'is_dismissable' => false,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $this->actingAs($user);

    Livewire::test(Widget::class)
        ->assertSee('Dismiss Me')
        ->assertSee('Keep Me')
        ->call('dismissAnnouncement', (string) $dismissableAnnouncement->id)
        ->assertDontSee('Dismiss Me')
        ->assertSee('Keep Me');

    $this->assertDatabaseHas('announcement_user_dismissals', [
        'announcement_id' => (string) $dismissableAnnouncement->id,
        'user_id' => (string) $user->id,
    ]);

    $this->get(route('announcements.index'))
        ->assertSuccessful()
        ->assertDontSee('Dismiss Me')
        ->assertSee('Keep Me');
});

it('ignores dismissal requests for announcements that are not dismissable', function (): void {
    $user = announcementUserWithPermissions(['announcements.view']);

    $announcement = Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Cannot Dismiss',
        'is_active' => true,
        'is_dismissable' => false,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $this->actingAs($user);

    Livewire::test(Widget::class)
        ->call('dismissAnnouncement', (string) $announcement->id)
        ->assertSee('Cannot Dismiss');

    $this->assertDatabaseMissing('announcement_user_dismissals', [
        'announcement_id' => (string) $announcement->id,
        'user_id' => (string) $user->id,
    ]);
});

it('excludes expired announcements from the active scope', function (): void {
    $user = User::factory()->create();

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Expired',
        'is_active' => true,
        'start_date' => now()->subDays(2),
        'end_date' => now()->subMinute(),
    ]);

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'Still Active',
        'is_active' => true,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);

    $titles = Announcement::query()->active()->pluck('title')->all();

    expect($titles)->toBe(['Still Active']);
});

it('includes announcements with null end_date in the active scope', function (): void {
    $user = User::factory()->create();

    Announcement::factory()->create([
        'created_by' => $user->id,
        'title' => 'No Expiry',
        'is_active' => true,
        'start_date' => now()->subHour(),
        'end_date' => null,
    ]);

    $titles = Announcement::query()->active()->pluck('title')->all();

    expect($titles)->toBe(['No Expiry']);
});

/**
 * @param  array<int, string>  $permissions
 */
function announcementUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Announcement Test Role '.str()->uuid(),
        'description' => 'Role created by announcement tests',
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
