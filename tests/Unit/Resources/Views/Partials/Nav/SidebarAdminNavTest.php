<?php

it('centers the administration header in the admin sidebar partial', function (): void {
    $view = file_get_contents(__DIR__.'/../../../../../../resources/views/partials/nav/sidebar-admin-nav.blade.php');

    expect($view)->toContain('flex flex-1 items-center justify-center px-4 py-2');
    expect($view)->toContain("<span class=\"text-center in-data-flux-sidebar-collapsed-desktop:hidden\">{{ __('Administration') }}</span>");
    expect($view)->toContain('in-data-flux-sidebar-collapsed-desktop:inline-flex">A</span>');
    expect($view)->toContain('<flux:sidebar.item icon="cog" :href="route(\'admin.settings.index\')" :current="request()->routeIs(\'admin.settings.*\')" wire:navigate data-test="admin-settings-link">');
    expect($view)->not->toContain('request()->routeIs(\'admin.users.*\') || request()->routeIs(\'admin.roles.*\') || request()->routeIs(\'admin.settings.*\')');
});
