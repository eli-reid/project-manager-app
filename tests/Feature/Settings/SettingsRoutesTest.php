<?php

it('registers admin settings route names for navigation and browser testing', function () {
    expect(route('admin.settings.index', absolute: false))->toBe('/admin/settings')
        ->and(route('admin.settings.import', absolute: false))->toBe('/admin/settings/import')
        ->and(route('admin.settings.export', absolute: false))->toBe('/admin/settings/export');
});
