<?php

namespace App\Core\Queue\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class QueueJobHistory extends Model
{
    use HasUlids;

    protected $table = 'queue_job_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'attempt' => 'int',
            'duration_ms' => 'int',
        ];
    }
}
