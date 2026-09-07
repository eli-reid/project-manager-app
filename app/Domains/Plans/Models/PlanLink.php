<?php

declare(strict_types=1);

namespace App\Domains\Plans\Models;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Database\Factories\PlanLinkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLink extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['from_revision_id', 'target_sheet_id', 'hotspot', 'label', 'auto_detected', 'created_by_id'];

    protected $attributes = ['auto_detected' => false];

    protected function casts(): array
    {
        return ['hotspot' => 'array', 'auto_detected' => 'boolean'];
    }

    public function fromRevision(): BelongsTo
    {
        return $this->belongsTo(PlanSheetRevision::class, 'from_revision_id');
    }

    public function targetSheet(): BelongsTo
    {
        return $this->belongsTo(PlanSheet::class, 'target_sheet_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected static function newFactory(): PlanLinkFactory
    {
        return PlanLinkFactory::new();
    }
}
