<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Livewire\SettingsGroupList;
use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('renders setting groups without aggregate count queries', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $suffix = Str::lower(Str::random(8));

    SettingsSqlite::query()->create([
        'key' => "app.name.{$suffix}",
        'value' => 'Project Manager',
        'type' => 'text',
        'group' => 'app',
        'is_visible' => true,
    ]);

    SettingsSqlite::query()->create([
        'key' => "app.timezone.{$suffix}",
        'value' => 'UTC',
        'type' => 'text',
        'group' => 'app',
        'is_visible' => true,
    ]);

    SettingsSqlite::query()->create([
        'key' => "system.maintenance_mode.{$suffix}",
        'value' => '0',
        'type' => 'boolean',
        'group' => 'system',
        'is_visible' => true,
    ]);

    SettingsSqlite::query()->create([
        'key' => "secret.hidden.{$suffix}",
        'value' => '1',
        'type' => 'boolean',
        'group' => 'secret',
        'is_visible' => false,
    ]);

    $connection = DB::connection('settings_sqlite');
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    Livewire::test(SettingsGroupList::class)
        ->assertSee('App')
        ->assertSee('System')
        ->assertDontSee('Secret');

    $queries = collect($connection->getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => strtolower($query));

    $aggregateCountQueries = $queries->filter(
        fn (string $query): bool => str_contains($query, 'count(*) as aggregate') && str_contains($query, 'from "settings"')
    );

    $distinctGroupQueries = $queries->filter(
        fn (string $query): bool => str_contains($query, 'select distinct "group" from "settings"')
    );

    expect($aggregateCountQueries)->toHaveCount(0)
        ->and($distinctGroupQueries->count())->toBeLessThanOrEqual(1);
});
