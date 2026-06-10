<?php

namespace App\Domains\Assets\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * @mixin IdeHelperAsset
 */
class Asset extends Model
{
    use HasUlids;

    protected $table = 'assets';

    protected $fillable = [
        'title',
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'folder_path',
        'created_by_id',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
}
