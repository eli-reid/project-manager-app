<?php

namespace App\Core\Queue\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperFailedJob
 */
class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $guarded = [];
}
