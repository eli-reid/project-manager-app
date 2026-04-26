<?php

namespace App\Domains\Submittals\Models;

use App\Domains\Projects\Models\Project;
use App\Domains\Documents\Models\Document;
use App\Domains\Submittals\Enums\SubmittalStatusEnum;
use App\Domains\Submittals\Models\SubmittalItem;
use App\Domains\Submittals\Models\SubmittalApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Submittal extends Model
{
    use SoftDeletes;

    protected $table = 'submittals';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'type',
        'spec_reference',
        'vendor',
        'need_by_date',
        'status',
        'submitted_by_id',
        'current_reviewer_id',
        'rejection_reason',
        'cancelled_at',
        'distributed_at',
    ];

    protected $casts = [
        'status' => SubmittalStatusEnum::class,
        'need_by_date' => 'date',
        'cancelled_at' => 'datetime',
        'distributed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubmittalItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SubmittalApproval::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
