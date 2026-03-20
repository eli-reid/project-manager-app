<?php

namespace App\Domains\Tasks\Models;

use App\Core\User\Models\User;
use App\Domains\Tasks\Database\Factories\TaskTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTaskTemplate
 */
class TaskTemplate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'task_category_id',
        'priority',
        'estimated_hours',
        'is_billable',
        'template_tasks',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'is_billable' => 'boolean',
            'template_tasks' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): TaskTemplateFactory
    {
        return TaskTemplateFactory::new();
    }
}
