<?php

namespace App\Domains\Dailies\Models;

use App\Core\User\Models\User;
use App\Domains\Dailies\Database\Factories\DailyReportFactory;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id',
        'custom_project_name',
        'user_id',
        'submitted_by_id',
        'report_date',
        'status',
        'work_performed',
        'materials_used',
        'equipment_used',
        'safety_issues',
        'delays',
        'visitors',
        'weather_condition',
        'temperature',
        'temperature_unit',
        'total_regular_hours',
        'total_overtime_hours',
        'total_hours',
        'additional_notes',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'work_performed' => 'array',
            'materials_used' => 'array',
            'equipment_used' => 'array',
            'safety_issues' => 'array',
            'delays' => 'array',
            'visitors' => 'array',
            'temperature' => 'float',
            'total_regular_hours' => 'float',
            'total_overtime_hours' => 'float',
            'total_hours' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    protected static function newFactory(): DailyReportFactory
    {
        return DailyReportFactory::new();
    }
}
