<?php

namespace App\Domains\Submittals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmittalItem extends Model
{
    use SoftDeletes;

    protected $table = 'submittal_items';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'submittal_id',
        'description',
        'manufacturer',
        'model',
        'part_number',
        'quantity',
        'unit',
        'status',
        'comments',
    ];

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }
}
