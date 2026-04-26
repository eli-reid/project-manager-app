<?php

namespace App\Domains\Submittals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Identity\Models\User;

class SubmittalApproval extends Model
{
    use SoftDeletes;

    protected $table = 'submittal_approvals';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'submittal_id',
        'step',
        'reviewer_id',
        'status',
        'reviewed_at',
        'comments',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
