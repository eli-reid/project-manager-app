<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Livewire\Admin\StockOrders\Show as AdminShow;
use App\Domains\Stock\Livewire\User\StockOrders\Form as UserForm;
use App\Domains\Stock\Livewire\User\Templates\FromTemplate;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists only the authenticated users stock orders on the user index', function (): void {
    $user = userWithStockWorkflowPermissions(['stock-orders.view-any']);
    $otherUser = User::factory()->create();

    $ownOrder = StockOrder::factory()->create([
        'user_id' => $user->id,
        'po_number' => 'PO-OWN-100',
    ]);

    StockOrder::factory()->create([
        'user_id' => $otherUser->id,
        'po_number' => 'PO-OTHER-200',
    ]);

    actingAs($user);

    get(route('stock-orders.index'))
        ->assertSuccessful()
        ->assertSee($ownOrder->po_number)
        ->assertDontSee('PO-OTHER-200');
});

it('allows a user to view their own stock order but forbids another users order', function (): void {
    $user = userWithStockWorkflowPermissions(['stock-orders.view']);
    $otherUser = User::factory()->create();

    $ownOrder = StockOrder::factory()->create([
        'user_id' => $user->id,
        'po_number' => 'PO-MINE-101',
    ]);

    $otherOrder = StockOrder::factory()->create([
        'user_id' => $otherUser->id,
        'po_number' => 'PO-THEIRS-202',
    ]);

    actingAs($user);

    get(route('stock-orders.show', $ownOrder))
        ->assertSuccessful()
        ->assertSee($ownOrder->po_number);

    get(route('stock-orders.show', $otherOrder))
        ->assertForbidden();
});

it('creates a stock order with multiple items through the user form component', function (): void {
    $user = userWithStockWorkflowPermissions(['stock-orders.create']);
    $project = Project::factory()->create(['is_active' => true]);

    actingAs($user);

    Livewire::test(UserForm::class)
        ->set('project_id', (string) $project->id)
        ->set('urgency', StockOrder::URGENCY_HIGH)
        ->set('po_number', 'PO-FORM-300')
        ->set('notes', 'Need materials this week')
        ->set('items', [
            ['item_name' => 'Concrete Mix', 'quantity' => 10, 'notes' => 'Type S'],
            ['item_name' => 'Rebar', 'quantity' => 24, 'notes' => 'Half inch'],
        ])
        ->call('save')
        ->assertRedirect();

    $order = StockOrder::query()
        ->where('user_id', $user->id)
        ->where('po_number', 'PO-FORM-300')
        ->first();

    expect($order)->not->toBeNull()
        ->and($order?->status)->toBe(StockOrder::STATUS_PENDING)
        ->and($order?->project_id)->toBe((string) $project->id)
        ->and($order?->items()->count())->toBe(2);
});

it('creates a stock order from a template', function (): void {
    $user = userWithStockWorkflowPermissions(['stock-orders.create', 'stock-order-templates.view']);
    $project = Project::factory()->create(['is_active' => true]);
    $template = StockOrderTemplate::factory()->globalTemplate()->create([
        'is_active' => true,
        'urgency' => StockOrder::URGENCY_MEDIUM,
        'template_items' => [
            ['item_name' => 'Safety Gloves', 'quantity' => 12],
            ['item_name' => 'Hard Hats', 'quantity' => 6],
        ],
    ]);

    actingAs($user);

    Livewire::test(FromTemplate::class, ['stockOrderTemplate' => $template])
        ->set('project_id', (string) $project->id)
        ->set('po_number', 'PO-TEMPLATE-400')
        ->set('items.0.notes', 'Large size')
        ->call('submit')
        ->assertRedirect();

    $order = StockOrder::query()
        ->where('user_id', $user->id)
        ->where('po_number', 'PO-TEMPLATE-400')
        ->first();

    expect($order)->not->toBeNull()
        ->and($order?->items()->count())->toBe(2)
        ->and($order?->items()->where('item_name', 'Safety Gloves')->exists())->toBeTrue();
});

it('processes stock order status transitions from the admin review component', function (): void {
    $processor = userWithStockWorkflowPermissions(['stock-orders.view-any', 'stock-orders.process']);
    $order = StockOrder::factory()->create([
        'status' => StockOrder::STATUS_PENDING,
    ]);

    actingAs($processor);

    Livewire::test(AdminShow::class, ['stockOrder' => $order])
        ->assertSee('Review Order')
        ->call('processOrder', StockOrder::STATUS_APPROVED)
        ->call('processOrder', StockOrder::STATUS_ORDERED)
        ->call('processOrder', StockOrder::STATUS_RECEIVED);

    expect($order->fresh()->status)->toBe(StockOrder::STATUS_RECEIVED);
});

it('enforces template ownership and global visibility in create-from-template routes', function (): void {
    $user = userWithStockWorkflowPermissions(['stock-orders.create', 'stock-order-templates.view']);
    $otherUser = User::factory()->create();

    $ownedTemplate = StockOrderTemplate::factory()->create([
        'created_by' => $user->id,
        'is_global' => false,
        'is_active' => true,
    ]);

    $globalTemplate = StockOrderTemplate::factory()->globalTemplate()->create([
        'is_active' => true,
    ]);

    $otherUsersPrivateTemplate = StockOrderTemplate::factory()->create([
        'created_by' => $otherUser->id,
        'is_global' => false,
        'is_active' => true,
    ]);

    actingAs($user);

    get(route('stock-orders.templates.from', $ownedTemplate))
        ->assertSuccessful();

    get(route('stock-orders.templates.from', $globalTemplate))
        ->assertSuccessful();

    get(route('stock-orders.templates.from', $otherUsersPrivateTemplate))
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithStockWorkflowPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Stock Workflow Test Role '.str()->uuid(),
        'description' => 'Role for stock workflow tests',
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
