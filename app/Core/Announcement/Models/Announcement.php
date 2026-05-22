<?php

namespace App\Core\Announcement\Models;

use App\Core\Announcement\Database\Factories\AnnouncementFactory;
use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAnnouncement
 */
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

    public function dismissedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_dismissals', 'announcement_id', 'user_id')
            ->withPivot('dismissed_at')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $builder): Builder => $builder->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn (Builder $builder): Builder => $builder->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query;
        }

        return $query->whereDoesntHave('dismissedByUsers', function (Builder $builder) use ($user): void {
            $builder->where('users.id', $user->id);
        });
    }

    public function scopeWithCreator(Builder $query): Builder
    {
        return $query->with('creator:id,first_name,last_name');
    }

    public function dismissFor(User $user): void
    {
        if (! $this->is_dismissable) {
            return;
        }

        $this->dismissedByUsers()->syncWithoutDetaching([
            $user->id => ['dismissed_at' => now()],
        ]);
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
