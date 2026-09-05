<?php

namespace App\Domains\Invoices\Models;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperInvoicePdfImport
 */
class InvoicePdfImport extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARSED = 'parsed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_IMPORTED = 'imported';

    protected $fillable = [
        'project_id',
        'created_by',
        'file_path',
        'status',
        'parsed_data',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isParsed(): bool
    {
        return $this->status === self::STATUS_PARSED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isImported(): bool
    {
        return $this->status === self::STATUS_IMPORTED;
    }
}
