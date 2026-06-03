<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperProjectTabDefinition
 */
class ProjectTabDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'mode_query_param',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
