<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;

it('enforces stock order mutability for update and delete policy checks', function (): void {
    $owner = userWithStockPolicyPermissions([
        'stock-orders.update',
        'stock-orders.delete',
        'stock-orders.view',
    ]);

    $pendingOrder = StockOrder::factory()->create([
        'user_id' => $owner->id,
        'status' => StockOrder::STATUS_PENDING,
    ]);

    $receivedOrder = StockOrder::factory()->create([
        'user_id' => $owner->id,
        'status' => StockOrder::STATUS_RECEIVED,
    ]);

    expect($owner->can('update', $pendingOrder))->toBeTrue()
        ->and($owner->can('delete', $pendingOrder))->toBeTrue()
        ->and($owner->can('update', $receivedOrder))->toBeFalse()
        ->and($owner->can('delete', $receivedOrder))->toBeFalse();
});

it('allows process policy only when a valid transition exists', function (): void {
    $processor = userWithStockPolicyPermissions(['stock-orders.process']);

    $pendingOrder = StockOrder::factory()->create([
        'status' => StockOrder::STATUS_PENDING,
    ]);

    $cancelledOrder = StockOrder::factory()->create([
        'status' => StockOrder::STATUS_CANCELLED,
    ]);

    expect($processor->can('process', $pendingOrder))->toBeTrue()
        ->and($processor->can('process', $cancelledOrder))->toBeFalse();
});

it('enforces template availability and ownership policy rules', function (): void {
    $owner = userWithStockPolicyPermissions([
        'stock-order-templates.view',
        'stock-order-templates.update',
        'stock-order-templates.delete',
    ]);

    $otherUser = User::factory()->create();

    $ownedTemplate = StockOrderTemplate::factory()->create([
        'created_by' => $owner->id,
        'is_global' => false,
        'is_active' => true,
    ]);

    $otherUsersTemplate = StockOrderTemplate::factory()->create([
        'created_by' => $otherUser->id,
        'is_global' => false,
        'is_active' => true,
    ]);

    $globalTemplate = StockOrderTemplate::factory()->globalTemplate()->create([
        'is_active' => true,
    ]);

    $inactiveGlobalTemplate = StockOrderTemplate::factory()->globalTemplate()->create([
        'is_active' => false,
    ]);

    expect($owner->can('view', $ownedTemplate))->toBeTrue()
        ->and($owner->can('update', $ownedTemplate))->toBeTrue()
        ->and($owner->can('delete', $ownedTemplate))->toBeTrue()
        ->and($owner->can('view', $otherUsersTemplate))->toBeFalse()
        ->and($owner->can('view', $globalTemplate))->toBeTrue()
        ->and($owner->can('update', $globalTemplate))->toBeFalse()
        ->and($owner->can('delete', $globalTemplate))->toBeFalse()
        ->and($owner->can('view', $inactiveGlobalTemplate))->toBeFalse();
});

it('supports stock order transition helpers and template availability scopes', function (): void {
    $owner = User::factory()->create();

    $order = StockOrder::factory()->create([
        'user_id' => $owner->id,
        'status' => StockOrder::STATUS_PENDING,
        'urgency' => StockOrder::URGENCY_HIGH,
    ]);

    $template = StockOrderTemplate::factory()->create([
        'created_by' => $owner->id,
        'is_global' => false,
        'is_active' => true,
    ]);

    expect($order->isPending())->toBeTrue()
        ->and($order->canTransitionTo(StockOrder::STATUS_APPROVED))->toBeTrue()
        ->and($order->transitionTo(StockOrder::STATUS_APPROVED))->toBeTrue()
        ->and($order->fresh()->status)->toBe(StockOrder::STATUS_APPROVED)
        ->and($template->isOwnedBy((string) $owner->id))->toBeTrue()
        ->and($template->isAvailableTo((string) $owner->id))->toBeTrue()
        ->and(StockOrder::query()->byStatus(StockOrder::STATUS_APPROVED)->count())->toBeGreaterThan(0)
        ->and(StockOrder::query()->byUrgency(StockOrder::URGENCY_HIGH)->count())->toBeGreaterThan(0)
        ->and(StockOrderTemplate::query()->availableToUser((string) $owner->id)->count())->toBeGreaterThan(0);
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithStockPolicyPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Stock Policy Test Role '.str()->uuid(),
        'description' => 'Role for stock policy tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 30,
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
