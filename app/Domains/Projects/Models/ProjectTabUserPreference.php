<?php

namespace App\Domains\Projects\Models;

use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProjectTabUserPreference
 */
class ProjectTabUserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tab_key',
        'sort_order',
        'is_hidden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
