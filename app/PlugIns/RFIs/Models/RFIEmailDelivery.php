<?php

namespace App\Domains\RFIs\Models;

use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RFIEmailDelivery extends Model
{
    use HasUlids;

    protected $table = 'rfi_email_deliveries';

    protected $fillable = [
        'rfi_id',
        'sent_by_id',
        'recipients',
        'subject',
        'cover_message',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(RFI::class, 'rfi_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }
}
