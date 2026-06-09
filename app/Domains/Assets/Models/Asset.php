<?php

namespace App\Domains\Assets\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
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
}
