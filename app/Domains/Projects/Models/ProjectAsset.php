<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectAsset extends Model
{
    protected $table = 'project_assets';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'asset_id',
        'created_by_id',
        'title',
    ];

    public function asset()
    {
        return $this->belongsTo(\App\Domains\Assets\Models\Asset::class, 'asset_id');
    }

    public function project()
    {
        return $this->belongsTo(\App\Domains\Projects\Models\Project::class, 'project_id');
    }
}
