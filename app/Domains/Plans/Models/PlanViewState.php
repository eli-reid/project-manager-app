<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Database\Factories\PlanViewStateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanViewState extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['user_id', 'plan_sheet_id', 'zoom', 'center_x', 'center_y', 'last_viewed_at'];

    protected function casts(): array
    {
        return ['zoom' => 'decimal:4', 'center_x' => 'decimal:6', 'center_y' => 'decimal:6', 'last_viewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(PlanSheet::class, 'plan_sheet_id');
    }

    protected static function newFactory(): PlanViewStateFactory
    {
        return PlanViewStateFactory::new();
    }
}
