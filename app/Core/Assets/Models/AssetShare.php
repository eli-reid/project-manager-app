<?php

namespace App\Core\Assets\Models;

use Illuminate\Database\Eloquent\Model;

class AssetShare extends Model
{
    protected $table = 'asset_shares';

    protected $fillable = [
        'asset_id',
        'token',
        'expires_at',
        'created_by_id',
    ];
}
