<?php

namespace App\Core\Announcement\Models;

use App\Core\Announcement\Database\Factories\AnnouncementFactory;
use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'type',
        'is_active',
        'is_dismissable',
        'start_date',
        'end_date',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'is_active' => 'boolean',
            'is_dismissable' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $builder): Builder => $builder->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn (Builder $builder): Builder => $builder->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
