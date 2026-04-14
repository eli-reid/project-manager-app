<?php

it('includes email management in the access admin domain navbar', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../app/Core/Identity/Resources/Views/layouts/access-admin.blade.php');

    expect($view)->toContain("@can('manage-email-accounts')");
    expect($view)->toContain(':href="route(\'admin.cpanel.manage.dashboard\')"');
    expect($view)->toContain(':current="request()->routeIs(\'admin.cpanel.manage.*\')"');
    expect($view)->toContain("{{ __('Email Management') }}");
});

it('keeps user admin layout as a compatibility wrapper', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../app/Core/Identity/Resources/Views/layouts/user-admin.blade.php');

    expect($view)->toContain("@include('core-user::layouts.access-admin')");
});
