<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Database\Factories\PlanAnnotationCommentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanAnnotationComment extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['plan_annotation_id', 'author_id', 'body'];

    public function annotation(): BelongsTo
    {
        return $this->belongsTo(PlanAnnotation::class, 'plan_annotation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function newFactory(): PlanAnnotationCommentFactory
    {
        return PlanAnnotationCommentFactory::new();
    }
}
