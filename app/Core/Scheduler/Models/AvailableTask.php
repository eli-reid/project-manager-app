<?php

namespace App\Core\Scheduler\Models;

use App\Core\Scheduler\Database\Factories\AvailableTaskFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAvailableTask
 */
class AvailableTask extends Model
{
    /** @use HasFactory<AvailableTaskFactory> */
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

    /**
     * Resolve the model factory for this model.
     */
    protected static function newFactory()
    {
        return AvailableTaskFactory::new();
    }
}
