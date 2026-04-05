<?php

use App\Core\Auth\Role\Livewire\Admin\Roles\Form as RoleForm;
use App\Core\Auth\Role\Livewire\Admin\Roles\Index as RoleIndex;
use App\Core\Auth\Role\Livewire\Admin\Roles\Users as RoleUsers;
use App\Core\Auth\User\Livewire\Admin\Users\Form as UserForm;
use App\Core\Auth\User\Livewire\Admin\Users\Index as UserIndex;
use Livewire\Attributes\Layout;

it('uses the layouts.admin attribute for admin role and user full-page components', function (string $componentClass): void {
    $reflection = new ReflectionClass($componentClass);
    $layoutAttributes = $reflection->getAttributes(Layout::class);

    expect($layoutAttributes)->toHaveCount(1);

    $layoutArguments = $layoutAttributes[0]->getArguments();

    expect($layoutArguments[0] ?? null)->toBe('core-user::layouts.user-admin');
})->with([
    RoleForm::class,
    RoleIndex::class,
    RoleUsers::class,
    UserForm::class,
    UserIndex::class,
]);
