<?php

it('includes clients and addresses in the client management admin navbar', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../resources/views/layouts/client-management-admin.blade.php');

    expect($view)->toContain(":href=\"route('admin.clients.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.clients.*')\"");
    expect($view)->toContain("{{ __('Clients') }}");

    expect($view)->toContain(":href=\"route('admin.addresses.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.addresses.*')\"");
    expect($view)->toContain("{{ __('Addresses') }}");
});
