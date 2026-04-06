<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\SettingsDatabaseProvisioner;
use Illuminate\Support\Facades\Schema;

it('uses an integer auto-incrementing primary key', function () {
    $model = new SettingsSqlite;

    expect($model->getKeyName())->toBe('id')
        ->and($model->getKeyType())->toBe('int')
        ->and($model->getIncrementing())->toBeTrue();
});

it('has encrypted column on settings sqlite schema', function () {
    app(SettingsDatabaseProvisioner::class)->ensureDatabase();

    expect(Schema::connection('settings_sqlite')->hasColumn('settings', 'encrypted'))->toBeTrue();
});
