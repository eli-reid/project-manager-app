<?php

namespace App\Core\Settings\Models;

use App\Core\Settings\Traits\EncryptableSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSettingsSqlite
 */
class SettingsSqlite extends Model
{
    use EncryptableSettings;

    /**
     * The connection name for the model.
     */
    protected $connection = 'settings_sqlite';

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'default_value',
        'display_name',
        'description',
        'type',
        'group',
        'options',
        'order',
        'is_public',
        'is_visible',
        'is_required',
        'encrypted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'encrypted' => 'boolean',
        'order' => 'integer',
    ];
}
