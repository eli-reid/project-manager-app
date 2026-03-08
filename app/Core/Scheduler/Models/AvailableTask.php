<?php

namespace App\Core\Scheduler\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailableTask extends Model
{
    /** @use HasFactory<\Database\Factories\Core\Scheduler\Models\AvailableTaskFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'available_tasks';

    protected $fillable = [
        'feature_type',
        'name',
        'description',
        'task_config',
        'is_active',
    ];

    protected $casts = [
        'task_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function scheduledTasks(): HasMany
    {
        return $this->hasMany(ScheduledTask::class, 'available_task_id');
    }
}
