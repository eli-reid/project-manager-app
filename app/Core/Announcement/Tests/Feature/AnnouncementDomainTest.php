<?php

use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Announcement\Livewire\Admin\Announcements\Form;
use App\Core\Announcement\Livewire\Admin\Announcements\Index;
use App\Core\Announcement\Models\Announcement;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
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
