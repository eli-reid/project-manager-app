<?php

use App\Settings\AppSettings;

it('returns an array of setting definitions', function (): void {
    $definitions = AppSettings::definitions();

    expect($definitions)->toBeArray();
    $keys = array_map(fn($s) => $s->key, $definitions);

    expect($keys)->toContain('app.name');
    expect($keys)->toContain('app.url');
});
