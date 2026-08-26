<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Mail\FormalRfiMailable;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Models\RFIEmailDelivery;
use App\Domains\RFIs\Services\RFIEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('emails a formal rfi and tracks recipients and send metadata', function (): void {
    Mail::fake();

    app(DomainPermissionSynchronizer::class)->sync();

    $sender = userWithRfiEmailPermissions(['rfis.view', 'rfis.email']);

    $project = Project::factory()->create();

    $rfi = RFI::factory()->submitted()->create([
        'project_id' => $project->id,
        'requested_by_id' => $sender->id,
        'subject' => 'Clarify beam splice plate dimensions',
        'body' => 'Please confirm final dimensions and weld schedule at grid C-7.',
    ]);

    $delivery = app(RFIEmailService::class)->sendFormalRfi(
        rfi: $rfi,
        sentBy: $sender,
        recipients: ['client@example.com', 'architect@example.com'],
        coverMessage: 'Please review and respond by end of week.',
        subject: 'Formal RFI Submission',
    );

    Mail::assertSent(FormalRfiMailable::class);

    expect($delivery)->toBeInstanceOf(RFIEmailDelivery::class)
        ->and($delivery->rfi_id)->toBe($rfi->id)
        ->and($delivery->sent_by_id)->toBe($sender->id)
        ->and($delivery->subject)->toBe('Formal RFI Submission')
        ->and($delivery->sent_at)->not->toBeNull()
        ->and($delivery->recipients)->toBe(['client@example.com', 'architect@example.com']);

    expect(
        RFIEmailDelivery::query()
            ->where('rfi_id', $rfi->id)
            ->count()
    )->toBe(1);
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithRfiEmailPermissions(array $permissions): User
{
    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'RFI Email Role '.str()->uuid(),
        'description' => 'Role for RFI email tests',
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
