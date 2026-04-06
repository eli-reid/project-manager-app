<?php

namespace App\Core\Queue\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperJobBatch
 */
class JobBatch extends Model
{
    protected $table = 'job_batches';

    public $timestamps = false;

    protected $guarded = [];
}
