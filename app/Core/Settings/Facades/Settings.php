<?php

namespace App\Core\Settings\Facades;

use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Core\Settings\Services\SettingValue get(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static bool set(string $key, mixed $value, ?string $description = null)
 * @method static array updateMany(array $settings)
 * @method static \Illuminate\Support\Collection getGroup(string $group)
 * @method static \Illuminate\Support\Collection getAllGrouped()
 * @method static void clearAllCache()
 *
 * @see SettingsSqliteService
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsSqliteService::class;
    }
}
