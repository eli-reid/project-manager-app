<?php

it('includes timecards and dailies in the time management admin navbar', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../app/Domains/Timecards/Resources/Views/layouts/time-management-admin.blade.php');

    expect($view)->toContain(":href=\"route('admin.timecards.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.timecards.*')\"");
    expect($view)->toContain("{{ __('Timecards') }}");

    expect($view)->toContain(":href=\"route('admin.dailies.index')\"");
    expect($view)->toContain(":current=\"request()->routeIs('admin.dailies.*')\"");
    expect($view)->toContain("{{ __('Dailies') }}");
});
